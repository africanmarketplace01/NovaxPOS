<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\StoreSettings;
use App\Models\StoreOrder;
use App\Models\landlord\DeliveryAgency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * Resolve store settings — abort 404 if store is not enabled.
     */
    private function resolveStore(): StoreSettings
    {
        $store = StoreSettings::instance();
        if (!$store->enabled) {
            abort(404, 'This store is not available.');
        }
        return $store;
    }

    // ── Public storefront homepage ────────────────────────────────────────────

    public function index(Request $request)
    {
        $store = $this->resolveStore();

        $featured = Product::ActiveStandard()
            ->where('featured', 1)
            ->with(['category', 'brand'])
            ->limit(8)
            ->get();

        $categories = Category::withCount(['product' => function ($q) {
            $q->where('is_active', true)->where('type', 'standard');
        }])->having('product_count', '>', 0)->get();

        return view('store.index', compact('store', 'featured', 'categories'));
    }

    // ── Product listing ───────────────────────────────────────────────────────

    public function products(Request $request)
    {
        $store = $this->resolveStore();

        $query = Product::ActiveStandard()->with(['category', 'brand']);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('code', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if (!$store->show_out_of_stock) {
            $query->where('qty', '>', 0);
        }

        $sort = $request->get('sort', 'name');
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest'     => $query->latest(),
            default      => $query->orderBy('name', 'asc'),
        };

        $products   = $query->paginate(20)->withQueryString();
        $categories = Category::withCount(['product' => function ($q) {
            $q->where('is_active', true)->where('type', 'standard');
        }])->having('product_count', '>', 0)->get();

        return view('store.products', compact('store', 'products', 'categories'));
    }

    // ── Product detail ────────────────────────────────────────────────────────

    public function product(Request $request, $id)
    {
        $store   = $this->resolveStore();
        $product = Product::ActiveStandard()->with(['category', 'brand', 'tax'])->findOrFail($id);

        $related = Product::ActiveStandard()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('store.product', compact('store', 'product', 'related'));
    }

    // ── Cart (session-based) ──────────────────────────────────────────────────

    public function cart(Request $request)
    {
        $store = $this->resolveStore();
        $cart  = session('store_cart', []);
        return view('store.cart', compact('store', 'cart'));
    }

    public function addToCart(Request $request)
    {
        $this->resolveStore();
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'qty'        => 'required|integer|min:1|max:999',
        ]);

        $product = Product::ActiveStandard()->findOrFail($request->product_id);
        $cart    = session('store_cart', []);
        $key     = $request->product_id;

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $request->qty;
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'code'       => $product->code,
                'price'      => $product->promotion_price ?: $product->price,
                'image'      => $product->image,
                'qty'        => $request->qty,
            ];
        }

        session(['store_cart' => $cart]);

        if ($request->expectsJson()) {
            return response()->json(['count' => count($cart), 'message' => 'Added to cart']);
        }
        return redirect()->back()->with('cart_success', $product->name . ' added to cart.');
    }

    public function updateCart(Request $request)
    {
        $this->resolveStore();
        $cart = session('store_cart', []);

        if ($request->action === 'remove') {
            unset($cart[$request->product_id]);
        } elseif ($request->action === 'update' && isset($cart[$request->product_id])) {
            $qty = (int) $request->qty;
            if ($qty <= 0) {
                unset($cart[$request->product_id]);
            } else {
                $cart[$request->product_id]['qty'] = $qty;
            }
        } elseif ($request->action === 'clear') {
            $cart = [];
        }

        session(['store_cart' => $cart]);

        if ($request->expectsJson()) {
            return response()->json(['count' => count($cart)]);
        }
        return redirect()->route('store.cart');
    }

    // ── Checkout ──────────────────────────────────────────────────────────────

    public function checkout(Request $request)
    {
        $store = $this->resolveStore();

        if (!$store->allow_orders) {
            return redirect()->route('store.cart')->with('error', 'Online ordering is not available yet.');
        }

        $cart = session('store_cart', []);
        if (empty($cart)) {
            return redirect()->route('store.index')->with('error', 'Your cart is empty.');
        }

        // Delivery agencies: global approved ones + this vendor's own agencies.
        $agencies = collect(); $deliveryZones = collect(); $deliveryPrices = collect();
        try {
            $tid = tenant()?->getTenantKey();
            $agencies = DeliveryAgency::where('status', 'approved')->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('owner_tenant_id')->orWhere('owner_tenant_id', $tid))
                ->orderBy('name')->get();
            $ids = $agencies->pluck('id');
            $deliveryZones  = \App\Models\landlord\DeliveryZone::whereIn('agency_id', $ids)->where('is_active', true)->orderBy('name')->get()->groupBy('agency_id');
            $deliveryPrices = \App\Models\landlord\DeliveryZonePrice::whereIn('agency_id', $ids)->get()->groupBy('agency_id');
        } catch (\Throwable $e) {
            // Landlord DB unavailable — gracefully skip delivery options
        }

        $paymentMethods = $store->enabledPaymentMethods();

        return view('store.checkout', compact('store', 'cart', 'agencies', 'deliveryZones', 'deliveryPrices', 'paymentMethods'));
    }

    public function placeOrder(Request $request)
    {
        $store = $this->resolveStore();

        if (!$store->allow_orders) {
            return redirect()->route('store.cart')->with('error', 'Online ordering is not available.');
        }

        $request->validate([
            'customer_name'      => 'required|string|max:120',
            'customer_email'     => 'nullable|email|max:120',
            'customer_phone'     => 'required|string|max:30',
            // Address is required when the order is being delivered by an agency.
            'customer_address'   => 'nullable|required_with:delivery_agency_id|string|max:500',
            'note'               => 'nullable|string|max:500',
            'payment_method'     => 'nullable|in:stripe,paystack,bank_transfer,mtn,orange,cod',
            'delivery_agency_id' => 'nullable|integer',
        ], [
            'customer_address.required_with' => 'A delivery address is required when choosing a delivery agency.',
        ]);

        $cart = session('store_cart', []);
        if (empty($cart)) {
            return redirect()->route('store.index')->with('error', 'Cart is empty.');
        }

        $items    = [];
        $subtotal = 0;

        foreach ($cart as $item) {
            $lineTotal = $item['price'] * $item['qty'];
            $subtotal += $lineTotal;
            $items[]   = [
                'product_id' => $item['product_id'],
                'name'       => $item['name'],
                'code'       => $item['code'] ?? '',
                'qty'        => $item['qty'],
                'price'      => $item['price'],
                'subtotal'   => $lineTotal,
            ];
        }

        // Tax
        $taxRate   = $store->tax_rate ?? 0;
        $taxAmount = round($subtotal * $taxRate / 100, 2);

        // Delivery
        $deliveryFee  = 0;
        $agencyName   = null;
        $agencyId     = null;
        $deliveryEst  = null;

        if ($request->filled('delivery_agency_id')) {
            try {
                $agency = DeliveryAgency::where('status', 'approved')->find($request->delivery_agency_id);
                if ($agency) {
                    $deliveryFee = $agency->base_rate;
                    $agencyName  = $agency->name;
                    $agencyId    = $agency->id;
                    $deliveryEst = $agency->deliveryEstimate();
                    // Zone-to-zone pricing overrides base rate when both zones are supplied.
                    if ($request->filled('from_zone_id') && $request->filled('to_zone_id')) {
                        $zp = \App\Models\landlord\DeliveryZonePrice::where('agency_id', $agency->id)
                            ->where('from_zone_id', $request->from_zone_id)
                            ->where('to_zone_id', $request->to_zone_id)->first();
                        if ($zp) $deliveryFee = $zp->price;
                    }
                }
            } catch (\Throwable $e) { /* landlord DB unavailable */ }
        }

        $total = $subtotal + $taxAmount + $deliveryFee;

        $order = StoreOrder::create([
            'order_number'         => StoreOrder::generateOrderNumber(),
            'customer_name'        => $request->customer_name,
            'customer_email'       => $request->customer_email,
            'customer_phone'       => $request->customer_phone,
            'customer_address'     => $request->customer_address,
            'items'                => $items,
            'subtotal'             => $subtotal,
            'tax_amount'           => $taxAmount,
            'total'                => $total,
            'currency'             => $store->store_currency ?? 'USD',
            'note'                 => $request->note,
            'status'               => 'pending',
            'payment_method'       => $request->payment_method ?? 'cod',
            'payment_status'       => 'pending',
            'delivery_agency_id'   => $agencyId,
            'delivery_agency_name' => $agencyName,
            'delivery_fee'         => $deliveryFee,
            'estimated_delivery'   => $deliveryEst,
            'source'               => 'store',
        ]);

        session()->forget('store_cart');

        return redirect()->route('store.order.success', $order->order_number);
    }

    // ── Order success page ────────────────────────────────────────────────────

    public function orderSuccess(Request $request, string $number)
    {
        $store = StoreSettings::instance();
        $order = StoreOrder::where('order_number', $number)->firstOrFail();
        return view('store.order-success', compact('store', 'order'));
    }

    // ── QR code page (share store link) ──────────────────────────────────────

    public function qr(Request $request)
    {
        $store    = $this->resolveStore();
        $storeUrl = url('/store');
        return view('store.qr', compact('store', 'storeUrl'));
    }
}
