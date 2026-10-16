<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\MarketplaceOrder;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\Tenant;
use App\Models\landlord\SaasSettings;
use App\Services\Marketplace\FeeSplit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceCheckoutController extends Controller
{
    // ── Cart (session-based) ──────────────────────────────────────────────────

    public function cart()
    {
        $cart     = session('mkt_cart', []);
        $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
        $currency = SaasSettings::get('currency') ?? 'USD';
        return view('landlord.marketplace.cart', compact('cart', 'subtotal', 'currency'));
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'listing_id' => 'required|integer',
            'qty'        => 'required|integer|min:1|max:999',
        ]);

        $listing = MarketplaceListing::visible()->findOrFail($request->listing_id);
        $cart    = session('mkt_cart', []);
        $key     = 'l' . $listing->id;

        if (isset($cart[$key])) {
            $cart[$key]['qty'] = min($cart[$key]['qty'] + $request->qty, 999);
        } else {
            $cart[$key] = [
                'listing_id'   => $listing->id,
                'tenant_id'    => $listing->tenant_id,
                'tenant_name'  => $listing->tenant_store_name ?: $listing->tenant_name,
                'product_id'   => $listing->product_id,
                'name'         => $listing->name,
                'price'        => $listing->effectivePrice(),
                'image_url'    => $listing->image_url,
                'qty'          => $request->qty,
            ];
        }

        session(['mkt_cart' => $cart]);

        if ($request->expectsJson()) {
            return response()->json([
                'count'   => array_sum(array_column($cart, 'qty')), // total items (matches nav badge)
                'lines'   => count($cart),
                'message' => 'Added to cart',
            ]);
        }
        return redirect()->back()->with('success', $listing->name . ' added to cart.');
    }

    public function updateCart(Request $request)
    {
        $cart = session('mkt_cart', []);
        $key  = 'l' . $request->listing_id;

        if ($request->action === 'remove') {
            unset($cart[$key]);
        } elseif ($request->action === 'update' && isset($cart[$key])) {
            $qty = (int) $request->qty;
            $qty > 0 ? $cart[$key]['qty'] = $qty : array_splice($cart, array_search($key, array_keys($cart)), 1);
        } elseif ($request->action === 'clear') {
            $cart = [];
        }

        session(['mkt_cart' => $cart]);

        if ($request->expectsJson()) {
            return response()->json(['count' => array_sum(array_column($cart, 'qty')), 'lines' => count($cart)]);
        }
        return redirect()->route('marketplace.cart');
    }

    // ── Checkout ──────────────────────────────────────────────────────────────

    public function checkout()
    {
        $cart = session('mkt_cart', []);
        if (empty($cart)) {
            return redirect()->route('marketplace.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
        // Only subscribed merchants' items carry a customer fee; marketplace
        // sellers pay a commission instead (FeeSplit).
        $feeInfo  = FeeSplit::customerFeeInfo(FeeSplit::forItems($cart));
        $agencies = DeliveryAgency::approved()->orderBy('name')->get();
        $settings = SaasSettings::allCached();
        $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();

        return view('landlord.marketplace.checkout', compact(
            'cart', 'subtotal', 'feeInfo', 'agencies', 'settings', 'customer'
        ));
    }

    public function placeOrder(Request $request)
    {
        $cart = session('mkt_cart', []);
        if (empty($cart)) {
            return redirect()->route('marketplace.index')->with('error', 'Your cart is empty.');
        }

        $request->validate([
            'customer_name'    => 'required|string|max:120',
            'customer_email'   => 'nullable|email|max:150',
            'customer_phone'   => 'required|string|max:30',
            'customer_address' => 'required|string|max:600',
            'payment_method'   => 'required|in:stripe,paystack,bank_transfer,mtn,orange,cod',
            'delivery_agency_id' => 'nullable|exists:saleprosaas_landlord.delivery_agencies,id',
            'note'             => 'nullable|string|max:600',
            'create_account'   => 'nullable|boolean',
            'account_password' => 'nullable|required_if:create_account,1|min:6',
        ]);

        // ── Account handling ────────────────────────────────────────────────
        $customer = \Illuminate\Support\Facades\Auth::guard('customer')->user();

        // Guest opted to create an account during checkout
        if (!$customer && $request->boolean('create_account') && $request->filled('customer_email')) {
            $exists = \App\Models\landlord\MarketplaceCustomer::where('email', $request->customer_email)->first();
            if (!$exists) {
                $customer = \App\Models\landlord\MarketplaceCustomer::create([
                    'name'      => $request->customer_name,
                    'email'     => $request->customer_email,
                    'phone'     => $request->customer_phone,
                    'password'  => $request->account_password,
                    'address'   => $request->customer_address,
                    'is_active' => true,
                ]);
                \Illuminate\Support\Facades\Auth::guard('customer')->login($customer);
            }
        }

        // Build items array and compute totals
        $items    = [];
        $subtotal = 0;

        foreach ($cart as $item) {
            $lineTotal = $item['price'] * $item['qty'];
            $subtotal += $lineTotal;
            $items[]   = array_merge($item, ['subtotal' => $lineTotal]);
        }

        // Platform fee: added to the total for subscribed merchants' items,
        // owed as commission by marketplace-only sellers (never charged to the customer).
        $feeSplit    = FeeSplit::forItems($items);
        $platformFee = $feeSplit['customer_fee'];

        // Delivery fee
        $deliveryFee  = 0;
        $agencyName   = null;
        $deliveryEst  = null;
        $agency       = null;

        if ($request->filled('delivery_agency_id')) {
            $agency      = DeliveryAgency::find($request->delivery_agency_id);
            if ($agency) {
                $deliveryFee = $agency->base_rate;
                $agencyName  = $agency->name;
                $deliveryEst = $agency->deliveryEstimate();
            }
        }

        $total = $subtotal + $platformFee + $deliveryFee;

        $order = MarketplaceOrder::create([
            'order_number'       => MarketplaceOrder::generateOrderNumber(),
            'customer_id'        => $customer?->id,
            'customer_name'      => $request->customer_name,
            'customer_email'     => $request->customer_email,
            'customer_phone'     => $request->customer_phone,
            'customer_address'   => $request->customer_address,
            'items'              => $items,
            'currency'           => SaasSettings::get('currency') ?? 'USD',
            'subtotal'           => $subtotal,
            'platform_fee'       => $platformFee,
            'delivery_fee'       => $deliveryFee,
            'total'              => $total,
            'delivery_agency_id'   => $request->delivery_agency_id,
            'delivery_agency_name' => $agencyName,
            'estimated_delivery'   => $deliveryEst,
            'payment_method'     => $request->payment_method,
            'payment_status'     => $request->payment_method === 'cod' ? 'pending' : 'pending',
            'note'               => $request->note,
            'status'             => 'pending',
        ] + FeeSplit::orderAttributes($feeSplit));

        // Start the tracking timeline
        $order->recordEvent('pending', 'Order placed', 'We have received your order and notified the vendor(s).', 'customer');

        // Fan-out: create a store_order in each tenant DB (shared with the mobile API)
        \App\Services\Marketplace\OrderFanout::run($order, $items, $agency);

        // Notify all parties (vendors, delivery agency, superadmins) — queued
        \App\Jobs\SendMarketplaceOrderNotifications::dispatch($order->id);

        // For COD — confirm immediately. For online payments — redirect to payment.
        if ($request->payment_method === 'cod') {
            session()->forget('mkt_cart');
            return redirect()->route('marketplace.order.success', $order->order_number);
        }

        // Online payment — store order number in session and redirect to payment
        session(['mkt_pending_order' => $order->order_number]);
        return redirect()->route('marketplace.payment', ['method' => $request->payment_method]);
    }

    /**
     * Create a store_order row in each relevant tenant's DB.
     */
    private function fanOutToTenants(MarketplaceOrder $order, array $items, ?DeliveryAgency $agency): void
    {
        // Group items by tenant
        $byTenant = [];
        foreach ($items as $item) {
            $byTenant[$item['tenant_id']][] = $item;
        }

        foreach ($byTenant as $tenantId => $tenantItems) {
            try {
                // Use the app's Tenant model — resolves on the correct central connection
                $tenant = \App\Models\landlord\Tenant::find($tenantId);
                if (!$tenant) {
                    Log::error("Marketplace fan-out: tenant {$tenantId} not found for order {$order->order_number}");
                    continue;
                }

                $tenantSubtotal = collect($tenantItems)->sum('subtotal');

                $tenant->run(function () use ($order, $tenantItems, $tenantSubtotal, $agency) {
                    \App\Models\StoreOrder::create([
                        'order_number'           => \App\Models\StoreOrder::generateOrderNumber(),
                        'customer_name'          => $order->customer_name,
                        'customer_email'         => $order->customer_email,
                        'customer_phone'         => $order->customer_phone,
                        'customer_address'       => $order->customer_address,
                        'items'                  => $tenantItems,
                        'subtotal'               => $tenantSubtotal,
                        'tax_amount'             => 0,
                        'total'                  => $tenantSubtotal,
                        'currency'               => $order->currency,
                        'note'                   => $order->note,
                        'payment_method'         => $order->payment_method,
                        'payment_status'         => $order->payment_status,
                        'delivery_agency_id'     => $order->delivery_agency_id,
                        'delivery_agency_name'   => $order->delivery_agency_name,
                        'delivery_fee'           => 0, // delivery fee retained by platform
                        'platform_fee'           => 0, // platform fee retained by platform
                        'source'                 => 'marketplace',
                        'marketplace_order_number' => $order->order_number,
                        'status'                 => 'pending',
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error("Marketplace fan-out failed for tenant {$tenantId}: " . $e->getMessage(), [
                    'order' => $order->order_number,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    // ── Payment callback placeholder ──────────────────────────────────────────

    public function payment(Request $request, string $method)
    {
        $orderNumber = session('mkt_pending_order');
        if (!$orderNumber) {
            return redirect()->route('marketplace.index');
        }
        $order    = MarketplaceOrder::where('order_number', $orderNumber)->firstOrFail();
        $settings = SaasSettings::allCached();
        return view('landlord.marketplace.payment', compact('order', 'method', 'settings'));
    }

    // ── Order success ─────────────────────────────────────────────────────────

    public function orderSuccess(string $orderNumber)
    {
        $order = MarketplaceOrder::where('order_number', $orderNumber)->firstOrFail();
        return view('landlord.marketplace.order-success', compact('order'));
    }
}
