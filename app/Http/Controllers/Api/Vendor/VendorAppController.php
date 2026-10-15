<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreOrder;
use App\Models\User;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\SourcingRequest;
use App\Models\landlord\SourcingResponse;
use App\Models\landlord\SourcingMessage;
use App\Services\Marketplace\SourcingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Vendor (tenant) mobile API — the "major features" for the vendor app:
 * sales dashboard, marketplace/store orders, products & stock, and the
 * product-sourcing requests + chat. Runs in tenant context (auth:sanctum).
 */
class VendorAppController extends Controller
{
    private function tenantId(): ?string
    {
        return tenant()?->getTenantKey() ?? (tenancy()->tenant?->id ?? null);
    }

    /** Friendly chat timestamp: time-only for today, "M j" this year, full date otherwise. */
    private function chatTime($dt): ?string
    {
        if (!$dt) return null;
        if ($dt->isToday())        return $dt->format('g:i A');
        if ($dt->isCurrentYear())  return $dt->format('M j, g:i A');
        return $dt->format('M j, Y g:i A');
    }

    // ── Auth (Sanctum token) ────────────────────────────────────────────────
    public function login(Request $request)
    {
        $data = $request->validate([
            'login'    => 'required|string',   // email or username
            'password' => 'required|string',
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $user = User::where($field, $data['login'])
            ->where('is_active', 1)->where('is_deleted', 0)->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }
        // Vendors = admin/owner roles only
        if (!in_array((int) $user->role_id, [1, 2])) {
            return response()->json(['message' => 'This account cannot use the vendor app.'], 403);
        }

        $token = $user->createToken('vendor-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role_id' => $user->role_id],
            'store' => tenant()->company_name ?? $this->tenantId(),
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        return response()->json(['user' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role_id' => $u->role_id],
            'store' => tenant()->company_name ?? $this->tenantId()]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['message' => 'Signed out.']);
    }

    /**
     * Acknowledge a push device. Targeting is done via OneSignal external-id /
     * tags set by the app, so this is a lightweight confirmation hook.
     */
    public function pushRegister(Request $request)
    {
        return response()->json(['ok' => true]);
    }

    // ── Sales dashboard ─────────────────────────────────────────────────────
    public function dashboard(Request $request)
    {
        $today = now()->startOfDay();
        $start = now()->subDays(29)->startOfDay()->toDateString();
        $end   = now()->toDateString();

        // Currency symbol
        $currency = 'USD';
        $gs = DB::table('general_settings')->latest()->first();
        if ($gs && $gs->currency) {
            $cur = DB::table('currencies')->where('id', $gs->currency)->first();
            $currency = $cur->symbol ?? $cur->code ?? 'USD';
        }

        // ── Headline (last 30 days) revenue / expense / profit ───────────────
        $sales    = (float) Sale::whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->whereNull('deleted_at')->sum(DB::raw('grand_total - shipping_cost'));
        $returns  = (float) \App\Models\Returns::whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->sum('grand_total');
        $income   = (float) \App\Models\Income::whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->sum('amount');
        $expense  = (float) \App\Models\Expense::whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->sum('amount');
        $purchase = (float) \App\Models\Purchase::whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->whereNull('deleted_at')
            ->where(fn ($q) => $q->where('purchase_type', '!=', 'opening balance')->orWhereNull('purchase_type'))->sum('grand_total');
        $revenue  = $sales - $returns + $income;
        $profit   = $revenue - $purchase - $expense;

        // ── Account balances ─────────────────────────────────────────────────
        $accounts = \App\Models\Account::where('is_active', 1)->orderByDesc('is_default')->get()->map(function ($a) {
            $recv = (float) DB::table('payments')->where('account_id', $a->id)->whereNotNull('sale_id')->sum('amount');
            $inc  = (float) DB::table('incomes')->where('account_id', $a->id)->sum('amount');
            $sent = (float) DB::table('payments')->where('account_id', $a->id)->whereNotNull('purchase_id')->sum('amount');
            $exp  = (float) DB::table('expenses')->where('account_id', $a->id)->sum('amount');
            $pay  = (float) DB::table('payrolls')->where('account_id', $a->id)->sum('amount');
            return [
                'id'         => $a->id,
                'name'       => $a->name,
                'account_no' => $a->account_no,
                'is_default' => (bool) $a->is_default,
                'balance'    => (float) ($a->initial_balance ?? 0) + $recv + $inc - $sent - $exp - $pay,
            ];
        });

        // ── Cash flow: last 6 months (received vs sent) ──────────────────────
        $cfMonths = []; $cfReceived = []; $cfSent = [];
        for ($i = 5; $i >= 0; $i--) {
            $m  = now()->subMonths($i);
            $s  = $m->copy()->startOfMonth()->toDateString();
            $e  = $m->copy()->endOfMonth()->toDateString();
            $rc = (float) DB::table('payments')->whereNotNull('sale_id')->whereDate('created_at', '>=', $s)->whereDate('created_at', '<=', $e)->sum('amount');
            $st = (float) DB::table('payments')->whereNotNull('purchase_id')->whereDate('created_at', '>=', $s)->whereDate('created_at', '<=', $e)->sum('amount');
            $st += (float) DB::table('expenses')->whereDate('created_at', '>=', $s)->whereDate('created_at', '<=', $e)->sum('amount');
            $cfMonths[]   = $m->format('M');
            $cfReceived[] = round($rc, 2);
            $cfSent[]     = round($st, 2);
        }

        // ── Yearly: Jan–Dec current year (sales vs purchases) ────────────────
        $yMonths = []; $ySales = []; $yPurch = [];
        for ($mo = 1; $mo <= 12; $mo++) {
            $s = now()->setDate(now()->year, $mo, 1)->startOfMonth();
            $sa = (float) Sale::whereYear('created_at', $s->year)->whereMonth('created_at', $mo)->whereNull('deleted_at')->sum('grand_total');
            $pu = (float) \App\Models\Purchase::whereYear('created_at', $s->year)->whereMonth('created_at', $mo)->whereNull('deleted_at')->sum('grand_total');
            $yMonths[] = $s->format('M');
            $ySales[]  = round($sa, 2);
            $yPurch[]  = round($pu, 2);
        }

        // ── Recent lists (tabs) ──────────────────────────────────────────────
        $recentSales = Sale::latest()->limit(6)->get()->map(fn ($s) => [
            'id'         => $s->id,
            'reference'  => $s->reference_no,
            'total'      => (float) $s->grand_total,
            'paid'       => (float) $s->paid_amount,
            'status'     => (int) $s->payment_status, // 1 pending, 2 due, 3 partial, 4 paid
            'created_at' => optional($s->created_at)->format('M j, Y g:i A'),
        ]);

        $recentOrders = StoreOrder::latest()->limit(6)->get()->map(fn ($o) => [
            'id'           => $o->id,
            'order_number' => $o->order_number,
            'customer'     => $o->customer_name,
            'total'        => (float) $o->total,
            'currency'     => $o->currency,
            'status'       => $o->status,
            'source'       => $o->source,
            'created_at'   => optional($o->created_at)->format('M j, Y g:i A'),
        ]);

        $platformFees = StoreOrder::where('platform_fee', '>', 0)->latest()->limit(6)->get()->map(fn ($o) => [
            'id'           => $o->id,
            'order_number' => $o->order_number,
            'platform_fee' => (float) $o->platform_fee,
            'total'        => (float) $o->total,
            'currency'     => $o->currency,
            'created_at'   => optional($o->created_at)->format('M j, Y g:i A'),
        ]);
        $platformFeeTotal = (float) StoreOrder::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('platform_fee');

        // ── Quick stats (kept for existing widgets) ──────────────────────────
        $lowStock = Product::where('is_active', 1)->whereColumn('qty', '<=', 'alert_quantity')->where('qty', '>=', 0)->count();

        return response()->json([
            'currency'         => $currency,
            'period'           => ['start' => $start, 'end' => $end],
            'today_sales'      => Sale::whereDate('created_at', $today)->count(),
            'today_revenue'    => (float) Sale::whereDate('created_at', $today)->sum('grand_total'),
            'month_revenue'    => (float) Sale::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('grand_total'),
            'revenue'          => round($revenue, 2),
            'expense'          => round($expense, 2),
            'purchase'         => round($purchase, 2),
            'profit'           => round($profit, 2),
            'low_stock'        => $lowStock,
            'pending_orders'   => StoreOrder::where('status', 'pending')->count(),
            'platform_fee_month' => round($platformFeeTotal, 2),
            'accounts'         => $accounts,
            'cash_flow'        => ['months' => $cfMonths, 'received' => $cfReceived, 'sent' => $cfSent],
            'yearly'           => ['months' => $yMonths, 'sales' => $ySales, 'purchases' => $yPurch],
            'recent_sales'     => $recentSales,
            'recent_orders'    => $recentOrders,
            'platform_fees'    => $platformFees,
        ]);
    }

    // ── Orders (store + marketplace, from store_orders) ─────────────────────
    public function orders(Request $request)
    {
        $q = StoreOrder::query();
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('source')) $q->where('source', $request->source);

        $page = $q->latest()->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(fn ($o) => [
                'id'           => $o->id,
                'order_number' => $o->order_number,
                'customer'     => $o->customer_name,
                'phone'        => $o->customer_phone,
                'total'        => (float) $o->total,
                'currency'     => $o->currency,
                'status'       => $o->status,
                'payment_status' => $o->payment_status,
                'source'       => $o->source,
                'items'        => $o->items,
                'created_at'   => optional($o->created_at)->format('M j, Y g:i A'),
            ]),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,completed',
        ]);
        $order   = StoreOrder::findOrFail($id);
        $current = $order->status;
        $next    = $data['status'];

        $svc = app(\App\Services\StoreOrderCompletionService::class);
        if (!$svc->canTransition($current, $next)) {
            return response()->json(['message' => "This order is {$current} and can't be changed to {$next}."], 422);
        }
        if ($next === $current) {
            return response()->json(['ok' => true, 'status' => $current]);
        }

        try {
            $svc->apply($order, $next, $request->user()->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Order status update failed: ' . $e->getMessage());
            return response()->json(['message' => 'Could not update the order: ' . $e->getMessage()], 500);
        }

        return response()->json(['ok' => true, 'status' => $order->status, 'sale_id' => $order->sale_id]);
    }

    /** Agents available to deliver this order (from the order's delivery agency). */
    public function orderAgents(Request $request, $id)
    {
        $order = StoreOrder::findOrFail($id);
        if (!$order->delivery_agency_id) {
            return response()->json(['data' => [], 'message' => 'No delivery agency on this order.']);
        }
        $agents = \App\Models\landlord\DeliveryAgent::where('agency_id', $order->delivery_agency_id)
            ->where('is_active', 1)->orderBy('name')->get()->map(fn ($a) => [
                'id' => $a->id, 'name' => $a->name, 'phone' => $a->phone, 'photo_url' => $a->photoUrl(),
            ]);
        return response()->json(['data' => $agents]);
    }

    /** Assign a delivery agent (rider) to an order. */
    public function assignAgent(Request $request, $id)
    {
        $data = $request->validate(['agent_id' => 'nullable|integer']);
        $order = StoreOrder::findOrFail($id);

        if (empty($data['agent_id'])) {
            $order->update(['delivery_agent_id' => null]);
            return response()->json(['ok' => true, 'agent' => null]);
        }
        $agent = \App\Models\landlord\DeliveryAgent::where('id', $data['agent_id'])
            ->where('agency_id', $order->delivery_agency_id)->first();
        if (!$agent) {
            return response()->json(['message' => "That agent isn't part of this order's delivery agency."], 422);
        }
        $order->update(['delivery_agent_id' => $agent->id]);

        // Keep the customer-facing marketplace order in sync so the agent shows in the customer app.
        if ($order->source === 'marketplace' && $order->marketplace_order_number) {
            try {
                \App\Models\landlord\MarketplaceOrder::where('order_number', $order->marketplace_order_number)
                    ->update(['delivery_agent_id' => $agent->id]);
            } catch (\Throwable $e) {}
        }
        return response()->json(['ok' => true, 'agent' => $this->agentPayload($agent)]);
    }

    /** Shared agent payload (name, phone, photo, bike) for order responses. */
    private function agentPayload($agent): ?array
    {
        if (!$agent) return null;
        $bike = \App\Models\landlord\DeliveryBike::where('agent_id', $agent->id)->first();
        return [
            'id'        => $agent->id,
            'name'      => $agent->name,
            'phone'     => $agent->phone,
            'photo_url' => $agent->photoUrl(),
            'bike'      => $bike ? trim($bike->label() . ' · ' . $bike->plate_number, ' ·') : null,
        ];
    }

    /** Generate a delivery note (Delivery record) from a completed order's sale. */
    public function createDeliveryNote(Request $request, $id)
    {
        $order = StoreOrder::findOrFail($id);
        if (!$order->sale_id) {
            return response()->json(['message' => 'Complete the order first — the delivery note is created from its sale.'], 422);
        }
        $existing = \App\Models\Delivery::where('sale_id', $order->sale_id)->first();
        if ($existing) {
            return response()->json(['ok' => true, 'reference' => $existing->reference_no, 'existing' => true]);
        }
        $agentName = $order->delivery_agent_id
            ? optional(\App\Models\landlord\DeliveryAgent::find($order->delivery_agent_id))->name
            : null;

        $delivery = \App\Models\Delivery::create([
            'reference_no' => 'dr-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
            'sale_id'      => $order->sale_id,
            'user_id'      => $request->user()->id,
            'address'      => $order->customer_address ?: 'N/A',
            'delivered_by' => $agentName ?: ($order->delivery_agency_name ?: null),
            'recieved_by'  => $order->customer_name,
            'status'       => 1, // 1=Packing, 2=Delivering, 3=Delivered
            'note'         => 'Created from order ' . $order->order_number,
        ]);
        return response()->json(['ok' => true, 'reference' => $delivery->reference_no], 201);
    }

    /** Mark an order as paid (records payment against its sale if completed). */
    public function markOrderPaid(Request $request, $id)
    {
        $order = StoreOrder::findOrFail($id);
        if (strtolower((string) $order->payment_status) === 'paid') {
            return response()->json(['ok' => true, 'payment_status' => 'paid']);
        }
        try {
            app(\App\Services\StoreOrderCompletionService::class)->markPaid($order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Mark order paid failed: ' . $e->getMessage());
            return response()->json(['message' => 'Could not mark as paid: ' . $e->getMessage()], 500);
        }
        return response()->json(['ok' => true, 'payment_status' => 'paid']);
    }

    // ── Full order detail (store + marketplace order) ────────────────────────
    public function orderDetail(Request $request, $id)
    {
        $o = StoreOrder::findOrFail($id);
        $items = collect(is_array($o->items) ? $o->items : [])->map(fn ($it) => [
            'name'     => $it['name'] ?? 'Item',
            'code'     => $it['code'] ?? null,
            'price'    => (float) ($it['price'] ?? 0),
            'qty'      => (float) ($it['qty'] ?? 1),
            'subtotal' => (float) ($it['subtotal'] ?? (($it['price'] ?? 0) * ($it['qty'] ?? 1))),
            'image'    => !empty($it['image']) ? asset('images/product/' . $it['image']) : null,
        ]);

        return response()->json([
            'order' => [
                'id'                       => $o->id,
                'order_number'             => $o->order_number,
                'source'                   => $o->source,
                'marketplace_order_number' => $o->marketplace_order_number,
                'status'                   => $o->status,
                'payment_method'           => $o->payment_method,
                'payment_status'           => $o->payment_status,
                'payment_reference'        => $o->payment_reference,
                'currency'                 => $o->currency,
                'customer'                 => [
                    'name'    => $o->customer_name,
                    'email'   => $o->customer_email,
                    'phone'   => $o->customer_phone,
                    'address' => $o->customer_address,
                ],
                'items'                    => $items,
                'subtotal'                 => (float) $o->subtotal,
                'tax_amount'               => (float) $o->tax_amount,
                'delivery_fee'             => (float) $o->delivery_fee,
                'platform_fee'             => (float) $o->platform_fee,
                'total'                    => (float) $o->total,
                'delivery'                 => [
                    'agency'    => $o->delivery_agency_name,
                    'agency_id' => $o->delivery_agency_id,
                    'fee'       => (float) $o->delivery_fee,
                    'estimated' => $o->estimated_delivery,
                    'agent'     => $o->delivery_agent_id
                        ? $this->agentPayload(\App\Models\landlord\DeliveryAgent::find($o->delivery_agent_id))
                        : null,
                ],
                'note'                     => $o->note,
                'created_at'               => optional($o->created_at)->format('M j, Y g:i A'),
            ],
        ]);
    }

    // ── Products & stock ────────────────────────────────────────────────────
    public function products(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $q = Product::where('is_active', 1)->with(['category:id,name', 'brand:id,title']);
        if ($request->filled('q')) {
            $term = $request->q;
            $q->where(fn ($sq) => $sq->where('name', 'like', "%$term%")->orWhere('code', 'like', "%$term%"));
        }
        if ($request->filled('category_id')) $q->where('category_id', $request->category_id);
        if ($request->filled('brand_id'))    $q->where('brand_id', $request->brand_id);
        if ($request->boolean('low_stock'))  $q->whereColumn('qty', '<=', 'alert_quantity');

        $page = $q->orderBy('name')->paginate(20);
        $ids  = collect($page->items())->pluck('id');

        $whQty = $warehouseId
            ? DB::table('product_warehouse')->whereIn('product_id', $ids)->where('warehouse_id', $warehouseId)->pluck('qty', 'product_id')
            : collect();

        // Which products are listed on the global marketplace (landlord DB)
        $listed = [];
        try {
            $listed = MarketplaceListing::where('tenant_id', $this->tenantId())
                ->whereIn('product_id', $ids)->where('is_active', true)
                ->pluck('product_id')->flip()->toArray();
        } catch (\Throwable $e) {}

        return response()->json([
            'data' => collect($page->items())->map(function ($p) use ($whQty, $warehouseId, $listed) {
                $img = null;
                foreach (explode(',', (string) $p->image) as $f) {
                    $f = trim($f);
                    if ($f !== '' && $f !== 'zummXD2dvAtI.png') { $img = asset('images/product/' . $f); break; }
                }
                $stock = $warehouseId !== null ? (float) ($whQty[$p->id] ?? 0) : (float) $p->qty;
                return [
                    'id'             => $p->id,
                    'name'           => $p->name,
                    'code'           => $p->code,
                    'price'          => (float) $p->price,
                    'cost'           => (float) $p->cost,
                    'qty'            => (float) $p->qty,
                    'warehouse_qty'  => $warehouseId !== null ? (float) ($whQty[$p->id] ?? 0) : null,
                    'stock'          => $stock,
                    'in_stock'       => $stock > 0,
                    'alert_quantity' => (float) $p->alert_quantity,
                    'low'            => $stock <= (float) $p->alert_quantity,
                    'image'          => $img,
                    'category'       => $p->category?->name,
                    'brand'          => $p->brand?->title,
                    'listed'         => isset($listed[$p->id]),
                ];
            }),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    // Metadata for the product screen + add-product form.
    public function productsMeta(Request $request)
    {
        $store = \App\Models\StoreSettings::instance();
        return response()->json([
            'marketplace_enabled' => (bool) ($store->marketplace_listed ?? false),
            'warehouses' => \App\Models\Warehouse::where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            'categories' => \App\Models\Category::where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            'brands'     => \App\Models\Brand::where('is_active', 1)->orderBy('title')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->title]),
            'units'      => \App\Models\Unit::where('is_active', 1)->orderBy('unit_name')->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->unit_name]),
            'taxes'      => \App\Models\Tax::where('is_active', 1)->orderBy('name')->get(['id', 'name', 'rate']),
        ]);
    }

    // Enable / disable a product on the global marketplace.
    public function listOnMarket(Request $request, $id)
    {
        $store = \App\Models\StoreSettings::instance();
        if (!$store->marketplace_listed) {
            return response()->json(['message' => 'Enable marketplace listing for your store first (Online Store settings).'], 422);
        }
        $product = Product::where('is_active', 1)->findOrFail($id);
        app(\App\Services\MarketplaceSyncService::class)->upsertListing($this->tenantId(), $product, $store);
        return response()->json(['ok' => true, 'listed' => true]);
    }

    public function unlistFromMarket(Request $request, $id)
    {
        try {
            MarketplaceListing::where('tenant_id', $this->tenantId())->where('product_id', $id)
                ->update(['is_active' => false]);
        } catch (\Throwable $e) {}
        return response()->json(['ok' => true, 'listed' => false]);
    }

    // Create a product — mirrors the web add-product flow: product types
    // (standard / digital / service) and multiple images (multi-size).
    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:191',
            'code'            => 'nullable|string|max:191',
            'type'            => 'nullable|in:standard,digital,service',
            'price'           => 'required|numeric|min:0',
            'cost'            => 'nullable|numeric|min:0',
            'qty'             => 'nullable|numeric|min:0',
            'alert_quantity'  => 'nullable|numeric|min:0',
            'category_id'     => 'nullable|integer',
            'brand_id'        => 'nullable|integer',
            'unit_id'         => 'nullable|integer',
            'tax_id'          => 'nullable|integer',
            'tax_method'      => 'nullable|in:1,2',
            'warehouse_id'    => 'nullable|integer',
            'product_details' => 'nullable|string',
            'images'          => 'nullable|array|max:6',
            'images.*'        => 'image|mimes:jpeg,jpg,png,webp|max:5120',
            'image'           => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120', // single fallback
        ]);

        $type    = $data['type'] ?? 'standard';
        $digital = in_array($type, ['digital', 'service']); // no stock / warehouse

        $code = $data['code'] ?? (string) ((int) Product::max('id') + 1000);
        if (Product::where('code', $code)->exists()) {
            return response()->json(['message' => 'A product with that code already exists.'], 422);
        }

        $unitId = $digital ? null : ($data['unit_id'] ?? optional(\App\Models\Unit::where('is_active', 1)->first())->id);

        // ── Images: accept an array ("images[]") or a single "image" ──────────
        $files = [];
        if ($request->hasFile('images')) $files = $request->file('images');
        elseif ($request->hasFile('image')) $files = [$request->file('image')];

        $imageNames = [];
        try {
            foreach (['', 'small', 'medium', 'large', 'xlarge'] as $sub) {
                $p = public_path('images/product/' . $sub);
                if (!is_dir($p)) @mkdir($p, 0755, true);
            }
            foreach ($files as $i => $file) {
                $ext  = $file->getClientOriginalExtension();
                $base = (config('database.connections.saleprosaas_landlord') && $this->tenantId() ? $this->tenantId() . '_' : '') . date('Ymdhis') . ($i + 1);
                $name = $base . '.' . $ext;
                $file->move(public_path('images/product'), $name);
                try {
                    $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                    $img = $manager->read(public_path('images/product/' . $name));
                    $img->resize(1000, 1250)->save(public_path('images/product/xlarge/' . $name));
                    $img->resize(500, 500)->save(public_path('images/product/large/' . $name));
                    $img->resize(250, 250)->save(public_path('images/product/medium/' . $name));
                    $img->resize(100, 100)->save(public_path('images/product/small/' . $name));
                } catch (\Throwable $e) { /* keep original even if resizing unavailable */ }
                $imageNames[] = $name;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Vendor product image failed: ' . $e->getMessage());
        }
        $imageField = $imageNames ? implode(',', $imageNames) : 'zummXD2dvAtI.png';

        try {
            return DB::transaction(function () use ($data, $code, $unitId, $imageField, $type, $digital) {
                $qty = $digital ? 0.0 : (float) ($data['qty'] ?? 0);
                $product = Product::create([
                    'name'             => $data['name'],
                    'code'             => $code,
                    'type'             => $type,
                    'barcode_symbology'=> 'C128',
                    'category_id'      => $data['category_id'] ?? null,
                    'brand_id'         => $data['brand_id'] ?? null,
                    'unit_id'          => $unitId,
                    'purchase_unit_id' => $unitId,
                    'sale_unit_id'     => $unitId,
                    'price'            => $data['price'],
                    'cost'             => $digital ? 0 : ($data['cost'] ?? 0),
                    'qty'              => $qty,
                    'alert_quantity'   => $digital ? 0 : ($data['alert_quantity'] ?? 0),
                    'tax_id'           => $data['tax_id'] ?? null,
                    'tax_method'       => $data['tax_method'] ?? 1,
                    'image'            => $imageField,
                    'product_details'  => isset($data['product_details']) ? str_replace('"', '@', $data['product_details']) : null,
                    'is_active'        => 1,
                    'featured'         => 0,
                    'promotion'        => 0,
                    'is_variant'       => 0,
                    'is_batch'         => 0,
                    'is_imei'          => 0,
                ]);

                // Physical stock only for standard products.
                if (!$digital) {
                    $warehouseId = $data['warehouse_id'] ?? optional(\App\Models\Warehouse::where('is_active', 1)->first())->id;
                    if ($warehouseId) {
                        DB::table('product_warehouse')->insert([
                            'product_id'   => $product->id,
                            'warehouse_id' => $warehouseId,
                            'qty'          => $qty,
                        ]);
                    }
                }
                return response()->json(['ok' => true, 'id' => $product->id, 'code' => $code], 201);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Vendor add product failed: ' . $e->getMessage());
            return response()->json(['message' => 'Could not create product: ' . $e->getMessage()], 500);
        }
    }

    public function updateStock(Request $request, $id)
    {
        $data = $request->validate(['qty' => 'required|numeric|min:0', 'price' => 'nullable|numeric|min:0']);
        $product = Product::findOrFail($id);
        $product->update(array_filter([
            'qty'   => $data['qty'],
            'price' => $data['price'] ?? null,
        ], fn ($v) => $v !== null));
        return response()->json(['ok' => true, 'qty' => (float) $product->qty]);
    }

    // ── Sourcing requests + chat (vendor side) ──────────────────────────────
    public function sourcingRequests(Request $request)
    {
        $tid = $this->tenantId();
        // Map request_id => own thread (SourcingResponse) for this vendor
        $threads = SourcingResponse::where('tenant_id', $tid)->get(['id', 'request_id'])->keyBy('request_id');

        // Unread customer messages per thread
        $unreadByThread = [];
        if ($threads->isNotEmpty()) {
            $unreadByThread = SourcingMessage::whereIn('response_id', $threads->pluck('id'))
                ->where('sender_type', 'customer')->whereNull('read_at')
                ->selectRaw('response_id, COUNT(*) as c')->groupBy('response_id')
                ->pluck('c', 'response_id')->toArray();
        }

        $page = SourcingRequest::where('status', 'open')->latest()->paginate(15);
        return response()->json([
            'data' => collect($page->items())->map(function ($r) use ($threads, $unreadByThread) {
                $thread = $threads[$r->id] ?? null;
                return [
                    'id'          => $r->id,
                    'title'       => $r->title,
                    'description' => $r->description,
                    'category'    => $r->category,
                    'budget'      => $r->budgetLabel(),
                    'quantity'    => $r->quantity,
                    'image_url'   => $r->imageUrl(),
                    'video_url'   => $r->videoUrl(),
                    'answered'    => $thread !== null,
                    'thread_id'   => $thread?->id,
                    'unread'      => $thread ? (int) ($unreadByThread[$thread->id] ?? 0) : 0,
                    'created_at'  => optional($r->created_at)->format('M j, Y'),
                    'created_ago' => optional($r->created_at)->diffForHumans(),
                ];
            }),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    // Vendor's own marketplace listings — for attaching product references in sourcing.
    public function sourcingListings(Request $request)
    {
        $tid = $this->tenantId();
        $q = MarketplaceListing::where('tenant_id', $tid)->where('is_active', true);
        if ($request->filled('q')) {
            $term = $request->q;
            $q->where(fn ($sq) => $sq->where('name', 'like', "%$term%")->orWhere('category', 'like', "%$term%"));
        }
        $rows = $q->orderBy('name')->limit(50)->get();
        return response()->json([
            'data' => $rows->map(fn ($l) => [
                'id'         => $l->id,
                'product_id' => $l->product_id,
                'name'       => $l->name,
                'price'      => (float) $l->effectivePrice(),
                'currency'   => $l->currency,
                'price_label'=> ($l->currency ? $l->currency . ' ' : '') . number_format($l->effectivePrice(), 2),
                'image_url'  => $l->image_url,
                'slug'       => $l->slug,
            ]),
        ]);
    }

    public function respondSourcing(Request $request, $id)
    {
        $tid = $this->tenantId();
        $req = SourcingRequest::findOrFail($id);
        $data = $request->validate([
            'message'      => 'required|string|max:2000',
            'price'        => 'nullable|numeric|min:0',
            'listing_id'   => 'nullable|integer',
            'listing_ids'  => 'nullable|array',
            'listing_ids.*'=> 'integer',
        ]);

        // Collect one or more product references (backward compatible with single listing_id)
        $ids = collect($data['listing_ids'] ?? [])->push($data['listing_id'] ?? null)->filter()->unique()->values();
        $listings = $ids->isNotEmpty()
            ? MarketplaceListing::where('tenant_id', $tid)->whereIn('id', $ids)->get()
            : collect();
        $primary = $listings->first();
        $name = MarketplaceListing::where('tenant_id', $tid)->value('tenant_store_name') ?? (tenant()->company_name ?? 'Vendor');

        $thread = SourcingResponse::updateOrCreate(
            ['request_id' => $req->id, 'tenant_id' => $tid],
            ['tenant_name' => $name, 'listing_id' => $primary?->id, 'message' => $data['message'], 'price' => $data['price'] ?? null, 'currency' => $primary?->currency],
        );

        $lastMsg = SourcingMessage::create([
            'response_id' => $thread->id, 'request_id' => $req->id, 'sender_type' => 'vendor',
            'tenant_id' => $tid, 'sender_name' => $name, 'body' => $data['message'], 'listing_id' => $primary?->id,
        ]);

        // Additional product references as their own reference messages.
        foreach ($listings->slice(1) as $l) {
            $lastMsg = SourcingMessage::create([
                'response_id' => $thread->id, 'request_id' => $req->id, 'sender_type' => 'vendor',
                'tenant_id' => $tid, 'sender_name' => $name, 'body' => null, 'listing_id' => $l->id,
            ]);
        }

        SourcingRequest::where('id', $req->id)->update(['responses_count' => SourcingResponse::where('request_id', $req->id)->count()]);

        try { app(SourcingNotifier::class)->notifyNewMessage($thread, $lastMsg); } catch (\Throwable $e) {}

        return response()->json(['ok' => true, 'thread_id' => $thread->id]);
    }

    public function threadMessages(Request $request, $responseId)
    {
        $tid = $this->tenantId();
        $thread = SourcingResponse::where('id', $responseId)->where('tenant_id', $tid)->firstOrFail();
        SourcingMessage::where('response_id', $thread->id)->where('sender_type', 'customer')->whereNull('read_at')->update(['read_at' => now()]);

        $messages = $thread->messages()->with('listing')->get()->map(fn ($m) => [
            'id' => $m->id, 'mine' => $m->sender_type === 'vendor', 'sender' => $m->sender_name,
            'body' => $m->body, 'time' => $this->chatTime($m->created_at),
            'listing' => $m->listing ? [
                'id'         => $m->listing->id,
                'product_id' => $m->listing->product_id,
                'name'       => $m->listing->name,
                'price'      => ($m->listing->currency ? $m->listing->currency . ' ' : '') . number_format($m->listing->effectivePrice(), 2),
                'image_url'  => $m->listing->image_url,
                'slug'       => $m->listing->slug,
            ] : null,
        ]);
        return response()->json(['messages' => $messages]);
    }

    public function sendThreadMessage(Request $request, $responseId)
    {
        $tid = $this->tenantId();
        $thread = SourcingResponse::where('id', $responseId)->where('tenant_id', $tid)->firstOrFail();
        $data = $request->validate([
            'body'       => 'nullable|string|max:2000|required_without:listing_id',
            'listing_id' => 'nullable|integer',
        ]);
        $listing = !empty($data['listing_id']) ? MarketplaceListing::where('tenant_id', $tid)->find($data['listing_id']) : null;

        $msg = SourcingMessage::create([
            'response_id' => $thread->id, 'request_id' => $thread->request_id, 'sender_type' => 'vendor',
            'tenant_id' => $tid, 'sender_name' => $thread->tenant_name, 'body' => $data['body'] ?? null, 'listing_id' => $listing?->id,
        ]);
        try { app(SourcingNotifier::class)->notifyNewMessage($thread, $msg); } catch (\Throwable $e) {}
        return response()->json(['ok' => true, 'id' => $msg->id]);
    }

    // ── POS: bootstrap lists (warehouses, billers, accounts, taxes, filters) ──
    public function posInit(Request $request)
    {
        $gs = DB::table('general_settings')->latest()->first();
        $currency = 'USD';
        if ($gs && $gs->currency) {
            $cur = DB::table('currencies')->where('id', $gs->currency)->first();
            $currency = $cur->symbol ?? $cur->code ?? 'USD';
        }

        return response()->json([
            'currency'        => $currency,
            'warehouses'      => \App\Models\Warehouse::where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            'billers'         => \App\Models\Biller::where('is_active', 1)->orderBy('name')->get(['id', 'name', 'company_name']),
            'accounts'        => \App\Models\Account::where('is_active', 1)->orderBy('name')->get(['id', 'name', 'is_default']),
            'taxes'           => \App\Models\Tax::where('is_active', 1)->orderBy('name')->get(['id', 'name', 'rate']),
            'categories'      => \App\Models\Category::where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            'brands'          => \App\Models\Brand::where('is_active', 1)->orderBy('title')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->title]),
            'payment_methods' => ['Cash', 'Card', 'Cheque', 'Bank Transfer', 'Mobile Money'],
            'walkin_customer' => \App\Models\Customer::where('is_active', 1)->orderBy('id')
                ->first(['id', 'name', 'phone_number']),
        ]);
    }

    // ── POS: products with per-warehouse stock + category/brand/featured filter
    public function posProducts(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');
        $q = Product::where('is_active', 1)->where('type', '!=', 'combo')
            ->with(['category:id,name', 'brand:id,title', 'tax:id,rate']);

        if ($request->filled('category_id')) $q->where('category_id', $request->category_id);
        if ($request->filled('brand_id'))    $q->where('brand_id', $request->brand_id);
        if ($request->boolean('featured'))   $q->where('featured', 1);
        if ($request->filled('q')) {
            $t = $request->q;
            $q->where(fn ($s) => $s->where('name', 'like', "%$t%")->orWhere('code', 'like', "%$t%"));
        }

        $page = $q->orderBy('name')->paginate(30);
        $ids  = collect($page->items())->pluck('id');
        $whQty = $warehouseId
            ? DB::table('product_warehouse')->whereIn('product_id', $ids)->where('warehouse_id', $warehouseId)->pluck('qty', 'product_id')
            : collect();

        return response()->json([
            'data' => collect($page->items())->map(fn ($p) => $this->posCard($p, $warehouseId !== null ? (float) ($whQty[$p->id] ?? 0) : null)),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    // ── POS: barcode / code lookup (scanner) ─────────────────────────────────
    public function posBarcode(Request $request, $code)
    {
        $p = Product::where('is_active', 1)->where('code', $code)
            ->with(['category:id,name', 'brand:id,title', 'tax:id,rate'])->first();
        if (!$p) {
            return response()->json(['message' => 'No product with that barcode.'], 404);
        }
        $warehouseId = $request->get('warehouse_id');
        $whQty = $warehouseId
            ? (float) (DB::table('product_warehouse')->where('product_id', $p->id)->where('warehouse_id', $warehouseId)->value('qty') ?? 0)
            : null;
        return response()->json(['product' => $this->posCard($p, $whQty)]);
    }

    private function posCard($p, ?float $whQty): array
    {
        $img = null;
        foreach (explode(',', (string) $p->image) as $f) {
            $f = trim($f);
            if ($f !== '' && $f !== 'zummXD2dvAtI.png') { $img = asset('images/product/' . $f); break; }
        }
        $promo = null;
        if ($p->promotion && (float) $p->promotion_price > 0) {
            $now = now();
            if ((!$p->starting_date || $now->gte($p->starting_date)) && (!$p->last_date || $now->lte($p->last_date))) {
                $promo = (float) $p->promotion_price;
            }
        }
        return [
            'id'            => $p->id,
            'name'          => $p->name,
            'code'          => $p->code,
            'price'         => (float) $p->price,
            'promo_price'   => $promo,
            'tax_rate'      => $p->tax ? (float) $p->tax->rate : 0.0,
            'tax_id'        => $p->tax_id,
            'image'         => $img,
            'category'      => $p->category?->name,
            'brand'         => $p->brand?->title,
            'featured'      => (bool) $p->featured,
            'stock'         => (float) $p->qty,
            'warehouse_qty' => $whQty,
        ];
    }

    // ── Customers: search + quick-add ────────────────────────────────────────
    public function customers(Request $request)
    {
        $q = \App\Models\Customer::where('is_active', 1);
        if ($request->filled('q')) {
            $t = $request->q;
            $q->where(fn ($s) => $s->where('name', 'like', "%$t%")->orWhere('phone_number', 'like', "%$t%")->orWhere('email', 'like', "%$t%"));
        }
        return response()->json([
            'data' => $q->orderBy('name')->limit(50)->get()->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone_number, 'email' => $c->email, 'address' => $c->address,
            ]),
        ]);
    }

    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:150',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
        ]);
        $group = \App\Models\CustomerGroup::where('is_active', 1)->first();
        $c = \App\Models\Customer::create([
            'customer_group_id' => $group?->id ?? 1,
            'user_id'           => $request->user()->id,
            'name'              => $data['name'],
            'phone_number'      => $data['phone'] ?? null,
            'email'             => $data['email'] ?? null,
            'address'           => $data['address'] ?? null,
            'is_active'         => 1,
        ]);
        return response()->json(['customer' => [
            'id' => $c->id, 'name' => $c->name, 'phone' => $c->phone_number, 'email' => $c->email, 'address' => $c->address,
        ]], 201);
    }

    // ── POS terminal: record a sale (customer/biller/warehouse/account, tax/discount) ──
    public function posSale(Request $request)
    {
        $data = $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer',
            'items.*.qty'          => 'required|numeric|min:0.01',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.tax_rate'     => 'nullable|numeric|min:0',
            'items.*.discount'     => 'nullable|numeric|min:0',
            'customer_id'          => 'nullable|integer',
            'customer_name'        => 'nullable|string|max:150',
            'biller_id'            => 'nullable|integer',
            'warehouse_id'         => 'nullable|integer',
            'account_id'           => 'nullable|integer',
            'order_tax_rate'       => 'nullable|numeric|min:0',
            'order_discount'       => 'nullable|numeric|min:0',
            'order_discount_type'  => 'nullable|in:Flat,Percentage',
            'order_discount_value' => 'nullable|numeric|min:0',
            'shipping_cost'        => 'nullable|numeric|min:0',
            'payment_method'       => 'nullable|string|max:50',
            'paid'                 => 'nullable|numeric|min:0',
        ]);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($data, $user) {
                $customer  = !empty($data['customer_id']) ? \App\Models\Customer::find($data['customer_id']) : \App\Models\Customer::where('is_active', 1)->first();
                $warehouse = !empty($data['warehouse_id']) ? \App\Models\Warehouse::find($data['warehouse_id']) : \App\Models\Warehouse::where('is_active', 1)->first();
                $biller    = !empty($data['biller_id']) ? \App\Models\Biller::find($data['biller_id']) : \App\Models\Biller::where('is_active', 1)->first();
                $accountId = $data['account_id']
                    ?? optional(\App\Models\Account::where('is_default', 1)->first())->id
                    ?? optional(\App\Models\Account::where('is_active', 1)->first())->id;

                $totalQty = 0.0; $productTotal = 0.0; $totalTax = 0.0; $totalDiscount = 0.0;
                $lines = [];
                foreach ($data['items'] as $it) {
                    $qty          = (float) $it['qty'];
                    $net          = (float) $it['price'];
                    $taxRate      = (float) ($it['tax_rate'] ?? 0);
                    $lineDiscount = (float) ($it['discount'] ?? 0);
                    $taxAmt       = ($net * $qty) * $taxRate / 100;
                    $lineTotal    = ($net * $qty) - $lineDiscount + $taxAmt;
                    $totalQty     += $qty;
                    $productTotal += $net * $qty;
                    $totalTax     += $taxAmt;
                    $totalDiscount += $lineDiscount;
                    $lines[] = compact('it', 'qty', 'net', 'taxRate', 'taxAmt', 'lineDiscount', 'lineTotal');
                }

                // Order discount: Flat amount or Percentage of the product total (web parity).
                $discType  = $data['order_discount_type'] ?? 'Flat';
                $discValue = (float) ($data['order_discount_value'] ?? $data['order_discount'] ?? 0);
                $orderDiscount = $discType === 'Percentage'
                    ? round($productTotal * $discValue / 100, 2)
                    : $discValue;

                $orderTaxRate  = (float) ($data['order_tax_rate'] ?? 0);
                $orderTax      = ($productTotal - $orderDiscount) * $orderTaxRate / 100;
                $shipping      = (float) ($data['shipping_cost'] ?? 0);
                $grand         = round($productTotal + $totalTax - $totalDiscount + $orderTax - $orderDiscount + $shipping, 2);
                $paid          = isset($data['paid']) ? (float) $data['paid'] : $grand;
                $ref           = 'posr-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

                $sale = Sale::create([
                    'reference_no'   => $ref,
                    'user_id'        => $user->id,
                    'customer_id'    => $customer?->id,
                    'warehouse_id'   => $warehouse?->id,
                    'biller_id'      => $biller?->id,
                    'item'           => count($data['items']),
                    'total_qty'      => $totalQty,
                    'total_discount' => $totalDiscount,
                    'total_tax'      => $totalTax,
                    'total_price'    => $productTotal,
                    'order_tax_rate' => $orderTaxRate,
                    'order_tax'      => $orderTax,
                    'order_discount_type'  => $discType,
                    'order_discount_value' => $discValue,
                    'order_discount' => $orderDiscount,
                    'shipping_cost'  => $shipping,
                    'grand_total'    => $grand,
                    'sale_status'    => 1,
                    'payment_status' => $paid >= $grand ? 4 : ($paid > 0 ? 2 : 1),
                    'paid_amount'    => $paid,
                    'sale_type'      => 'pos',
                    'created_at'     => now(),
                ]);

                // Resolve each product's sale unit (sale_unit_id is NOT NULL with no default).
                $saleUnits = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->pluck('sale_unit_id', 'id');

                foreach ($lines as $ln) {
                    DB::table('product_sales')->insert([
                        'sale_id'        => $sale->id,
                        'product_id'     => $ln['it']['product_id'],
                        'qty'            => $ln['qty'],
                        'sale_unit_id'   => (int) ($saleUnits[$ln['it']['product_id']] ?? 0),
                        'net_unit_price' => $ln['net'],
                        'discount'       => $ln['lineDiscount'],
                        'tax_rate'       => $ln['taxRate'],
                        'tax'            => $ln['taxAmt'],
                        'total'          => $ln['lineTotal'],
                    ]);
                    Product::where('id', $ln['it']['product_id'])->decrement('qty', $ln['qty']);
                    if ($warehouse) {
                        DB::table('product_warehouse')
                            ->where('product_id', $ln['it']['product_id'])
                            ->where('warehouse_id', $warehouse->id)
                            ->decrement('qty', $ln['qty']);
                    }
                }

                if ($paid > 0) {
                    DB::table('payments')->insert(array_filter([
                        'sale_id'           => $sale->id,
                        'account_id'        => $accountId,
                        'payment_reference' => 'pay-' . $ref,
                        'amount'            => $paid,
                        'change'            => max(0, $paid - $grand),
                        'paying_method'     => $data['payment_method'] ?? 'Cash',
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ], fn ($v) => $v !== null));
                }

                return response()->json([
                    'ok'        => true,
                    'sale_id'   => $sale->id,
                    'reference' => $ref,
                    'total'     => $grand,
                    'paid'      => $paid,
                    'change'    => max(0, $paid - $grand),
                ], 201);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Vendor POS sale failed: ' . $e->getMessage());
            return response()->json(['message' => 'Could not complete the sale: ' . $e->getMessage()], 500);
        }
    }

    // ── Reports (product + warehouse summary) ───────────────────────────────
    public function reports(Request $request)
    {
        $warehouseId = $request->get('warehouse_id');

        $top = DB::table('product_sales')
            ->join('products', 'products.id', '=', 'product_sales.product_id')
            ->join('sales', 'sales.id', '=', 'product_sales.sale_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->when($warehouseId, fn ($q) => $q->where('sales.warehouse_id', $warehouseId))
            ->select(
                'products.id', 'products.name', 'products.code', 'products.image',
                'categories.name as category', 'brands.title as brand',
                DB::raw('SUM(product_sales.qty) as sold'),
                DB::raw('SUM(product_sales.total) as revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.code', 'products.image', 'categories.name', 'brands.title')
            ->orderByDesc('sold')
            ->limit(10)->get();

        $topProducts = $top->map(function ($r) {
            $img = null;
            foreach (explode(',', (string) $r->image) as $f) {
                $f = trim($f);
                if ($f !== '' && $f !== 'zummXD2dvAtI.png') { $img = asset('images/product/' . $f); break; }
            }
            return [
                'id'       => $r->id,
                'name'     => $r->name,
                'code'     => $r->code,
                'category' => $r->category,
                'brand'    => $r->brand,
                'image'    => $img,
                'sold'     => (float) $r->sold,
                'revenue'  => (float) $r->revenue,
            ];
        });

        // Stock value / out-of-stock, optionally scoped to a warehouse.
        if ($warehouseId) {
            $stockValue = (float) DB::table('product_warehouse')
                ->join('products', 'products.id', '=', 'product_warehouse.product_id')
                ->where('product_warehouse.warehouse_id', $warehouseId)
                ->sum(DB::raw('product_warehouse.qty * products.cost'));
            $outOfStock = DB::table('product_warehouse')
                ->join('products', 'products.id', '=', 'product_warehouse.product_id')
                ->where('product_warehouse.warehouse_id', $warehouseId)
                ->where('products.is_active', 1)
                ->where('product_warehouse.qty', '<=', 0)->count();
        } else {
            $stockValue = (float) DB::table('products')->where('is_active', 1)->sum(DB::raw('qty * cost'));
            $outOfStock = Product::where('is_active', 1)->where('qty', '<=', 0)->count();
        }

        return response()->json([
            'stock_value'  => $stockValue,
            'out_of_stock' => $outOfStock,
            'top_products' => $topProducts,
            'warehouses'   => \App\Models\Warehouse::where('is_active', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ── Attendance: clock in / out ───────────────────────────────────────────
    private function fmtTime($t): ?string
    {
        if (!$t) return null;
        try { return \Carbon\Carbon::parse($t)->format('h:i A'); } catch (\Throwable $e) { return $t; }
    }

    public function attendanceStatus(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $open = \App\Models\Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where(fn ($q) => $q->whereNull('checkout')->orWhere('checkout', ''))
            ->latest('id')->first();

        // Recent — formatted date + 12h times.
        $recent = \App\Models\Attendance::where('user_id', $user->id)
            ->orderByDesc('date')->orderByDesc('id')->limit(30)->get()->map(fn ($a) => [
                'date'     => $a->date ? \Carbon\Carbon::parse($a->date)->format('M. j, Y') : '',
                'checkin'  => $this->fmtTime($a->checkin),
                'checkout' => $this->fmtTime($a->checkout),
            ]);

        // Shift (if the user's employee has one) + HRM default times.
        $employee = \App\Models\Employee::where('user_id', $user->id)->first();
        $shift = ($employee && $employee->shift_id) ? \App\Models\Shift::find($employee->shift_id) : null;
        $hrm   = \App\Models\HrmSetting::latest()->first();

        // Monthly report — present (green) / absent (red) / null (today+future).
        $monthStr = $request->get('month', now()->format('Y-m'));
        try { $mStart = \Carbon\Carbon::createFromFormat('Y-m-d', $monthStr . '-01')->startOfDay(); }
        catch (\Throwable $e) { $mStart = now()->startOfMonth(); }
        $daysInMonth = $mStart->daysInMonth;
        $present = \App\Models\Attendance::where('user_id', $user->id)
            ->whereYear('date', $mStart->year)->whereMonth('date', $mStart->month)
            ->get()->map(fn ($a) => (int) \Carbon\Carbon::parse($a->date)->day)->unique()->flip();
        $status = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $mStart->copy()->day($d);
            if (isset($present[$d]))                       $status[$d] = 'present';
            elseif ($date->isPast() && !$date->isToday())  $status[$d] = 'absent';
            else                                            $status[$d] = null;
        }

        return response()->json([
            'clocked_in' => $open !== null,
            'since'      => $this->fmtTime($open?->checkin),
            'today'      => $today,
            'recent'     => $recent,
            'shift'      => $shift ? [
                'name'      => $shift->name,
                'start'     => $this->fmtTime($shift->start_time),
                'end'       => $this->fmtTime($shift->end_time),
                'grace_in'  => $shift->grace_in,
                'grace_out' => $shift->grace_out,
                'total_hours' => $shift->total_hours,
            ] : null,
            'hrm'        => $hrm ? ['checkin' => $this->fmtTime($hrm->checkin), 'checkout' => $this->fmtTime($hrm->checkout)] : null,
            'report'     => [
                'month'         => $mStart->format('Y-m'),
                'label'         => $mStart->format('F Y'),
                'days_in_month' => $daysInMonth,
                'first_weekday' => (int) $mStart->copy()->day(1)->dayOfWeek, // 0=Sun
                'status'        => $status,
            ],
        ]);
    }

    public function attendanceClock(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();
        $nowHM = now()->format('H:i');

        $open = \App\Models\Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where(fn ($q) => $q->whereNull('checkout')->orWhere('checkout', ''))
            ->latest('id')->first();

        if ($open) {
            $open->update(['checkout' => $nowHM]);
            return response()->json(['ok' => true, 'clocked_in' => false, 'checkout' => $nowHM]);
        }

        $employeeId = (int) (\App\Models\Employee::where('user_id', $user->id)->value('id') ?? 0);
        \App\Models\Attendance::create([
            'date'        => $today,
            'employee_id' => $employeeId,
            'user_id'     => $user->id,
            'checkin'     => $nowHM,
            'checkout'    => '',
            'status'      => 1,
            'note'        => 'Mobile clock-in',
        ]);
        return response()->json(['ok' => true, 'clocked_in' => true, 'checkin' => $nowHM]);
    }

    // ── Setup: add warehouse ─────────────────────────────────────────────────
    public function storeWarehouse(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'email'   => 'nullable|email|max:191',
            'address' => 'nullable|string|max:255',
        ]);
        if (\App\Models\Warehouse::where('name', $data['name'])->where('is_active', 1)->exists()) {
            return response()->json(['message' => 'A warehouse with that name already exists.'], 422);
        }

        $warehouse = \App\Models\Warehouse::create(array_merge($data, ['is_active' => true]));
        // Seed stock rows (qty 0) for every product, mirroring the web flow.
        foreach (Product::pluck('id') as $pid) {
            DB::table('product_warehouse')->insert(['product_id' => $pid, 'warehouse_id' => $warehouse->id, 'qty' => 0]);
        }
        return response()->json(['ok' => true, 'id' => $warehouse->id], 201);
    }

    // ── Setup: people (staff users) ──────────────────────────────────────────
    public function peopleMeta(Request $request)
    {
        return response()->json([
            'roles'      => \App\Models\Roles::where('is_active', 1)->where('id', '!=', 5)->orderBy('name')->get(['id', 'name']),
            'warehouses' => \App\Models\Warehouse::where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            'billers'    => \App\Models\Biller::where('is_active', 1)->orderBy('name')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->name ?: $b->company_name]),
        ]);
    }

    public function people(Request $request)
    {
        $users = User::leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.is_active', 1)->where('users.role_id', '!=', 5)
            ->where(fn ($q) => $q->where('users.is_deleted', 0)->orWhereNull('users.is_deleted'))
            ->orderBy('users.name')
            ->select('users.id', 'users.name', 'users.email', 'users.phone', 'roles.name as role')
            ->limit(100)->get();
        return response()->json(['data' => $users]);
    }

    public function storePerson(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255|unique:users,name',
            'email'        => 'required|email|max:255|unique:users,email',
            'password'     => 'required|string|min:6',
            'role_id'      => 'required|integer|not_in:5',
            'phone'        => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|integer',
            'biller_id'    => 'nullable|integer',
        ]);

        // SaaS package user-limit guard (mirrors the web flow).
        if (config('database.connections.saleprosaas_landlord')) {
            try {
                $tid = $this->tenantId();
                if ($tid) {
                    $lt = \App\Models\landlord\Tenant::with('package')->find($tid);
                    $limit = $lt?->package?->user_limit;
                    if ($limit && DB::table('users')->where('is_deleted', false)->count() >= $limit) {
                        return response()->json(['message' => "You have reached your package limit of {$limit} users."], 422);
                    }
                }
            } catch (\Throwable $e) {}
        }

        $user = User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => bcrypt($data['password']),
            'role_id'      => $data['role_id'],
            'phone'        => $data['phone'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'biller_id'    => $data['biller_id'] ?? null,
            'is_active'    => true,
            'is_deleted'   => false,
        ]);
        return response()->json(['ok' => true, 'id' => $user->id], 201);
    }

    // ── Setup: online store settings (excludes QR + global-market) ───────────
    public function getStoreSettings(Request $request)
    {
        $s = \App\Models\StoreSettings::instance();
        return response()->json([
            'settings' => [
                'enabled'            => (bool) $s->enabled,
                'store_name'         => $s->store_name,
                'store_about'        => $s->store_about,
                'store_logo_url'     => $s->store_logo ? asset('storage/' . $s->store_logo) : null,
                'store_banner_url'   => $s->store_banner ? asset('storage/' . $s->store_banner) : null,
                'store_email'        => $s->store_email,
                'whatsapp_number'    => $s->whatsapp_number,
                'order_note'         => $s->order_note,
                'store_instagram'    => $s->store_instagram,
                'store_facebook'     => $s->store_facebook,
                'tax_rate'           => (float) $s->tax_rate,
                'show_out_of_stock'  => (bool) $s->show_out_of_stock,
                'allow_orders'       => (bool) $s->allow_orders,
                'accept_stripe'      => (bool) $s->accept_stripe,
                'accept_paystack'    => (bool) $s->accept_paystack,
                'accept_bank_transfer' => (bool) $s->accept_bank_transfer,
                'accept_mtn'         => (bool) $s->accept_mtn,
                'accept_orange'      => (bool) $s->accept_orange,
                'accept_cod'         => (bool) $s->accept_cod,
            ],
        ]);
    }

    public function updateStoreSettings(Request $request)
    {
        // Multipart sends empty text inputs as "" — normalise to null so
        // nullable rules (e.g. email) don't fail on an empty string.
        foreach (['store_name', 'store_about', 'store_email', 'whatsapp_number', 'order_note', 'store_instagram', 'store_facebook'] as $k) {
            if ($request->input($k) === '') $request->merge([$k => null]);
        }

        $data = $request->validate([
            'enabled'            => 'nullable|boolean',
            'store_name'         => 'nullable|string|max:191',
            'store_about'        => 'nullable|string|max:2000',
            'store_email'        => 'nullable|email|max:191',
            'whatsapp_number'    => 'nullable|string|max:50',
            'order_note'         => 'nullable|string|max:1000',
            'store_instagram'    => 'nullable|string|max:191',
            'store_facebook'     => 'nullable|string|max:191',
            'tax_rate'           => 'nullable|numeric|min:0|max:100',
            'show_out_of_stock'  => 'nullable|boolean',
            'allow_orders'       => 'nullable|boolean',
            'accept_stripe'      => 'nullable|boolean',
            'accept_paystack'    => 'nullable|boolean',
            'accept_bank_transfer' => 'nullable|boolean',
            'accept_mtn'         => 'nullable|boolean',
            'accept_orange'      => 'nullable|boolean',
            'accept_cod'         => 'nullable|boolean',
            'store_logo'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
            'store_banner'       => 'nullable|image|mimes:jpeg,jpg,png,webp|max:6144',
        ]);
        unset($data['store_logo'], $data['store_banner']); // handled as files below

        $s = \App\Models\StoreSettings::instance();
        $s->update($data); // note: marketplace_listed & QR are intentionally NOT editable here

        if ($request->hasFile('store_logo') && $request->file('store_logo')->isValid()) {
            $s->update(['store_logo' => $request->file('store_logo')->store('store', 'public')]);
        }
        if ($request->hasFile('store_banner') && $request->file('store_banner')->isValid()) {
            $s->update(['store_banner' => $request->file('store_banner')->store('store', 'public')]);
        }
        return response()->json([
            'ok' => true,
            'store_logo_url'   => $s->store_logo ? asset('storage/' . $s->store_logo) : null,
            'store_banner_url' => $s->store_banner ? asset('storage/' . $s->store_banner) : null,
        ]);
    }

    // ── Setup: catalog taxonomy (categories, brands, units, taxes) ───────────
    public function taxonomy(Request $request, string $kind)
    {
        switch ($kind) {
            case 'categories':
                return response()->json(['data' => \App\Models\Category::where('is_active', 1)->orderBy('name')->get(['id', 'name'])]);
            case 'brands':
                return response()->json(['data' => \App\Models\Brand::where('is_active', 1)->orderBy('title')->get()->map(fn ($b) => ['id' => $b->id, 'name' => $b->title])]);
            case 'units':
                return response()->json(['data' => \App\Models\Unit::where('is_active', 1)->orderBy('unit_name')->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->unit_name . ' (' . $u->unit_code . ')'])]);
            case 'taxes':
                return response()->json(['data' => \App\Models\Tax::where('is_active', 1)->orderBy('name')->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->name . ' — ' . rtrim(rtrim((string) $t->rate, '0'), '.') . '%'])]);
            default:
                return response()->json(['message' => 'Unknown list.'], 404);
        }
    }

    public function storeTaxonomy(Request $request, string $kind)
    {
        switch ($kind) {
            case 'categories':
                $d = $request->validate(['name' => 'required|string|max:191']);
                $c = \App\Models\Category::create(['name' => $d['name'], 'slug' => \Illuminate\Support\Str::slug($d['name']), 'is_active' => 1]);
                return response()->json(['ok' => true, 'id' => $c->id], 201);
            case 'brands':
                $d = $request->validate(['name' => 'required|string|max:191']);
                $b = \App\Models\Brand::create(['title' => $d['name'], 'is_active' => 1]);
                return response()->json(['ok' => true, 'id' => $b->id], 201);
            case 'units':
                $d = $request->validate(['name' => 'required|string|max:191', 'code' => 'nullable|string|max:50']);
                $u = \App\Models\Unit::create([
                    'unit_name' => $d['name'],
                    'unit_code' => $d['code'] ?? strtoupper(substr($d['name'], 0, 3)),
                    'base_unit' => null, 'operator' => '*', 'operation_value' => 1, 'is_active' => 1,
                ]);
                return response()->json(['ok' => true, 'id' => $u->id], 201);
            case 'taxes':
                $d = $request->validate(['name' => 'required|string|max:191', 'rate' => 'required|numeric|min:0|max:100']);
                $t = \App\Models\Tax::create(['name' => $d['name'], 'rate' => $d['rate'], 'is_active' => 1]);
                return response()->json(['ok' => true, 'id' => $t->id], 201);
            default:
                return response()->json(['message' => 'Unknown list.'], 404);
        }
    }
}
