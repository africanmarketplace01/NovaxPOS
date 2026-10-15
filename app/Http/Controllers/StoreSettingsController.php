<?php

namespace App\Http\Controllers;

use App\Models\StoreSettings;
use App\Models\StoreOrder;
use Illuminate\Http\Request;

class StoreSettingsController extends Controller
{
    public function index()
    {
        $store  = StoreSettings::instance();
        $orders = StoreOrder::latest()->paginate(20);
        $storeUrl = url('/store');
        return view('backend.store.settings', compact('store', 'orders', 'storeUrl'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'store_name'        => 'nullable|string|max:120',
            'store_about'       => 'nullable|string|max:1000',
            'whatsapp_number'   => 'nullable|string|max:30',
            'order_note'        => 'nullable|string|max:500',
            'store_currency'    => 'nullable|string|max:10',
            'tax_rate'          => 'nullable|numeric|min:0|max:100',
            'store_email'       => 'nullable|email|max:120',
            'store_instagram'   => 'nullable|string|max:120',
            'store_facebook'    => 'nullable|string|max:120',
        ]);

        $store = StoreSettings::instance();
        $store->update([
            'enabled'              => $request->boolean('enabled'),
            'store_name'           => $request->store_name,
            'store_about'          => $request->store_about,
            'whatsapp_number'      => $request->whatsapp_number,
            'order_note'           => $request->order_note,
            'store_currency'       => $request->store_currency,
            'show_out_of_stock'    => $request->boolean('show_out_of_stock'),
            'allow_orders'         => $request->boolean('allow_orders'),
            // New fields
            'tax_rate'             => $request->filled('tax_rate') ? (float) $request->tax_rate : 0,
            'store_email'          => $request->store_email,
            'store_instagram'      => $request->store_instagram,
            'store_facebook'       => $request->store_facebook,
            'accept_stripe'        => $request->boolean('accept_stripe'),
            'accept_paystack'      => $request->boolean('accept_paystack'),
            'accept_bank_transfer' => $request->boolean('accept_bank_transfer'),
            'accept_mtn'           => $request->boolean('accept_mtn'),
            'accept_orange'        => $request->boolean('accept_orange'),
            'accept_cod'           => $request->boolean('accept_cod'),
            'marketplace_listed'   => $request->boolean('marketplace_listed'),
        ]);

        // Handle banner upload
        if ($request->hasFile('store_banner') && $request->file('store_banner')->isValid()) {
            $path = $request->file('store_banner')->store('store', 'public');
            $store->update(['store_banner' => $path]);
        }

        return redirect()->route('store.settings')->with('success', 'Store settings saved.');
    }

    public function orders()
    {
        $store  = StoreSettings::instance();
        $orders = StoreOrder::latest()->paginate(25);
        return view('backend.store.orders', compact('store', 'orders'));
    }

    /**
     * Platform fee invoices issued to this vendor by the marketplace.
     */
    public function platformFees()
    {
        $tenantId = tenancy()->tenant?->id ?? config('app.tenant_id');

        try {
            $feeRequests = \Illuminate\Support\Facades\DB::connection('saleprosaas_landlord')
                ->table('platform_fee_requests')
                ->where('tenant_id', $tenantId)
                ->where('status', '!=', 'draft')
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            $feeRequests = collect();
        }

        // Money the platform sent this store for online-paid marketplace orders.
        try {
            $payouts = \Illuminate\Support\Facades\DB::connection('saleprosaas_landlord')
                ->table('vendor_payouts')
                ->where('tenant_id', $tenantId)
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            $payouts = collect();
        }

        return view('backend.store.platform-fees', compact('feeRequests', 'payouts'));
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,processing,shipped,delivered,completed,cancelled']);
        $order   = StoreOrder::findOrFail($id);
        $current = $order->status;
        $next    = $request->status;

        $svc = app(\App\Services\StoreOrderCompletionService::class);
        if (!$svc->canTransition($current, $next)) {
            $msg = "This order is {$current} and can't be changed to {$next}.";
            if ($request->expectsJson()) return response()->json(['message' => $msg], 422);
            return redirect()->back()->with('error', $msg);
        }
        if ($next !== $current) {
            try {
                // Completing creates a Sale; cancelling a completed order reverses it.
                $svc->apply($order, $next, \Auth::id());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Store order status update failed: ' . $e->getMessage());
                $msg = 'Could not update the order: ' . $e->getMessage();
                if ($request->expectsJson()) return response()->json(['message' => $msg], 500);
                return redirect()->back()->with('error', $msg);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => $order->status, 'sale_id' => $order->sale_id]);
        }
        return redirect()->back()->with('success', 'Order status updated.');
    }
}
