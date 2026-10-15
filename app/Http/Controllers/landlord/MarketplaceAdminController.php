<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\MarketplaceFee;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\MarketplaceOrder;
use App\Models\landlord\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MarketplaceAdminController extends Controller
{
    // ── Dashboard / Overview ──────────────────────────────────────────────────

    public function index()
    {
        $stats = [
            'total_listings'  => MarketplaceListing::count(),
            'active_listings' => MarketplaceListing::visible()->count(),
            'total_orders'    => MarketplaceOrder::count(),
            'pending_orders'  => MarketplaceOrder::where('status', 'pending')->count(),
            'total_revenue'   => MarketplaceOrder::where('payment_status', 'paid')->sum('platform_fee'),
            'total_sales'     => MarketplaceOrder::where('payment_status', 'paid')->sum('total'),
            'agencies'        => DeliveryAgency::count(),
            'pending_agencies'=> DeliveryAgency::pending()->count(),
            'shops'           => MarketplaceListing::visible()->distinct('tenant_id')->count('tenant_id'),
        ];

        $recentOrders = MarketplaceOrder::latest()->limit(10)->get();

        $revenueChart = MarketplaceOrder::selectRaw(
            'DATE_FORMAT(created_at, "%Y-%m") as month, SUM(platform_fee) as revenue, COUNT(*) as orders'
        )
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->get();

        return view('superadmin.marketplace.index', compact('stats', 'recentOrders', 'revenueChart'));
    }

    // ── Fee Management ────────────────────────────────────────────────────────

    public function fees()
    {
        $globalFee    = MarketplaceFee::globalFee();
        $tenantFees   = MarketplaceFee::where('scope', 'tenant')->with([])->get();
        $categoryFees = MarketplaceFee::where('scope', 'category')->get();
        $tenants      = Tenant::active()->orderBy('id')->get();

        // Marketplace-only sellers pay a commission instead of a subscription.
        $settings = \App\Models\landlord\SaasSettings::allCached();
        $sellerCommission = [
            'percent'  => $settings['seller_commission_percent'] ?? $globalFee->percent_fee,
            'flat'     => $settings['seller_commission_flat'] ?? 0,
            'due_days' => $settings['seller_fee_due_days'] ?? 7,
        ];
        $sellerCount = Tenant::where('subscription_type', Tenant::MARKETPLACE_SUBSCRIPTION)->count();
        $heldSellers = \Illuminate\Support\Facades\Schema::connection('saleprosaas_landlord')->hasColumn('tenants', 'marketplace_hold')
            ? Tenant::where('marketplace_hold', true)->orderBy('marketplace_hold_at')->get()
            : collect();

        return view('superadmin.marketplace.fees', compact(
            'globalFee', 'tenantFees', 'categoryFees', 'tenants', 'sellerCommission', 'sellerCount', 'heldSellers'
        ));
    }

    /** Default commission for marketplace-only sellers (per-seller overrides still win). */
    public function updateSellerCommission(Request $request)
    {
        $data = $request->validate([
            'seller_percent'  => 'required|numeric|min:0|max:50',
            'seller_flat'     => 'required|numeric|min:0|max:999',
            'seller_due_days' => 'required|integer|min:1|max:60',
        ]);

        \App\Models\landlord\SaasSettings::bulkSet([
            'seller_commission_percent' => $data['seller_percent'],
            'seller_commission_flat'    => $data['seller_flat'],
            'seller_fee_due_days'       => $data['seller_due_days'],
        ]);

        return back()->with('message', 'Marketplace seller commission updated.');
    }

    public function updateFees(Request $request)
    {
        $request->validate([
            'global_percent' => 'required|numeric|min:0|max:50',
            'global_flat'    => 'required|numeric|min:0|max:999',
            'global_label'   => 'nullable|string|max:80',
        ]);

        MarketplaceFee::updateOrCreate(
            ['scope' => 'global', 'scope_id' => null],
            [
                'percent_fee' => $request->global_percent,
                'flat_fee'    => $request->global_flat,
                'label'       => $request->global_label ?? 'Platform processing fee',
                'is_active'   => true,
            ]
        );

        return back()->with('message', 'Global fee updated.');
    }

    public function storeTenantFee(Request $request)
    {
        $request->validate([
            'tenant_id'   => 'required|string|max:100',
            'percent_fee' => 'required|numeric|min:0|max:50',
            'flat_fee'    => 'required|numeric|min:0|max:999',
        ]);

        MarketplaceFee::updateOrCreate(
            ['scope' => 'tenant', 'scope_id' => $request->tenant_id],
            [
                'percent_fee' => $request->percent_fee,
                'flat_fee'    => $request->flat_fee,
                'label'       => 'Platform fee (tenant override)',
                'is_active'   => true,
            ]
        );

        return back()->with('message', 'Tenant fee saved.');
    }

    public function deleteFee(int $id)
    {
        MarketplaceFee::where('id', $id)->where('scope', '!=', 'global')->delete();
        return back()->with('message', 'Fee removed.');
    }

    // ── Delivery Agencies ─────────────────────────────────────────────────────

    public function agencies(Request $request)
    {
        $status   = $request->get('status', 'all');
        $query    = DeliveryAgency::orderByDesc('created_at');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $agencies = $query->paginate(20)->withQueryString();
        return view('superadmin.marketplace.agencies', compact('agencies', 'status'));
    }

    public function approveAgency(int $id)
    {
        $agency = DeliveryAgency::findOrFail($id);
        $agency->update(['status' => 'approved', 'approved_at' => now(), 'rejection_reason' => null]);

        $this->notifyAgency($agency, 'approved');

        return back()->with('message', $agency->name . ' has been approved.');
    }

    public function rejectAgency(Request $request, int $id)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $agency = DeliveryAgency::findOrFail($id);
        $agency->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);

        $this->notifyAgency($agency, 'rejected', $request->reason);

        return back()->with('message', $agency->name . ' has been rejected.');
    }

    public function suspendAgency(int $id)
    {
        $agency = DeliveryAgency::findOrFail($id);
        $agency->update(['status' => 'suspended']);
        return back()->with('message', $agency->name . ' suspended.');
    }

    private function notifyAgency(DeliveryAgency $agency, string $decision, ?string $reason = null): void
    {
        try {
            $body = $decision === 'approved'
                ? "Congratulations! Your delivery agency \"{$agency->name}\" has been approved on our platform. You can now start receiving delivery requests."
                : "Unfortunately, your delivery agency registration has been rejected.\n\nReason: " . ($reason ?: 'No reason provided.') . "\n\nYou may re-apply after addressing the above.";

            Mail::raw($body, fn($m) => $m->to($agency->email)->subject("Your Agency Registration — " . ucfirst($decision)));
        } catch (\Throwable $e) {
            Log::warning('Agency notification failed: ' . $e->getMessage());
        }
    }

    // ── Product Listings ──────────────────────────────────────────────────────

    public function listings(Request $request)
    {
        $query = MarketplaceListing::orderByDesc('created_at');

        if ($request->filled('tenant')) {
            $query->where('tenant_id', $request->tenant);
        }
        if ($request->filled('approved')) {
            $query->where('approved', $request->approved === '1');
        }

        $listings = $query->paginate(30)->withQueryString();
        $tenants  = MarketplaceListing::selectRaw('tenant_id, tenant_store_name')->distinct()->get();

        return view('superadmin.marketplace.listings', compact('listings', 'tenants'));
    }

    public function toggleListing(int $id)
    {
        $listing = MarketplaceListing::findOrFail($id);
        $listing->update(['approved' => !$listing->approved]);
        return back()->with('message', 'Listing visibility updated.');
    }

    public function deleteListing(int $id)
    {
        MarketplaceListing::findOrFail($id)->delete();
        return back()->with('message', 'Listing removed.');
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function orders(Request $request)
    {
        $query = MarketplaceOrder::orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('order_number', 'like', "%{$q}%")
                   ->orWhere('customer_name', 'like', "%{$q}%")
                   ->orWhere('customer_email', 'like', "%{$q}%");
            });
        }

        $orders = $query->paginate(25)->withQueryString();

        $summary = [
            'total_platform_fees' => MarketplaceOrder::where('payment_status', 'paid')->sum('platform_fee'),
            'total_sales'         => MarketplaceOrder::where('payment_status', 'paid')->sum('total'),
            'pending_count'       => MarketplaceOrder::where('status', 'pending')->count(),
        ];

        return view('superadmin.marketplace.orders', compact('orders', 'summary'));
    }

    public function updateOrderStatus(Request $request, int $id)
    {
        $request->validate([
            'status'         => 'required|in:pending,processing,shipped,delivered,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded',
        ]);

        $order = MarketplaceOrder::findOrFail($id);
        $prevStatus  = $order->status;
        $prevPayment = $order->payment_status;
        $order->update(array_filter([
            'status'          => $request->status,
            'payment_status'  => $request->payment_status,
            'tracking_number' => $request->tracking_number,
        ]));

        // Record tracking timeline events for anything that changed
        if ($request->status && $request->status !== $prevStatus) {
            $labels = [
                'pending'    => 'Order placed',
                'processing' => 'Order is being processed',
                'shipped'    => 'Order shipped',
                'delivered'  => 'Order delivered',
                'completed'  => 'Order completed',
                'cancelled'  => 'Order cancelled',
            ];
            $note = ($request->status === 'shipped' && $request->tracking_number)
                ? 'Tracking #: ' . $request->tracking_number
                : $request->input('note');
            $order->recordEvent($request->status, $labels[$request->status] ?? ucfirst($request->status), $note, 'admin');
        }
        if ($request->payment_status && $request->payment_status === 'paid' && $prevPayment !== 'paid') {
            $order->recordEvent('paid', 'Payment confirmed', null, 'admin');
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('message', 'Order updated.');
    }

    public function showOrder(int $id)
    {
        $order = MarketplaceOrder::findOrFail($id);
        return view('superadmin.marketplace.order-detail', compact('order'));
    }

    public function markNotificationRead(int $id)
    {
        DB::connection('saleprosaas_landlord')
            ->table('marketplace_notifications')
            ->where('id', $id)
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
