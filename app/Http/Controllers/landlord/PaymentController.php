<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Tenant;
use App\Models\landlord\TenantPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = TenantPayment::with('tenant');

        if ($request->tenant_id) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();
        $tenants  = Tenant::orderBy('company_name')->get(['id', 'company_name']);

        $totalRevenue = TenantPayment::where('status', 'completed')->sum('amount');
        $pendingCount = TenantPayment::where('status', 'pending')->count();

        return view('superadmin.payments.index', compact(
            'payments', 'tenants', 'totalRevenue', 'pendingCount'
        ));
    }

    /** Manually record a payment (e.g. bank transfer) */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'         => 'required|exists:saleprosaas_landlord.tenants,id',
            'amount'            => 'required|numeric|min:0.01',
            'currency'          => 'required|string|max:3',
            'paid_by'           => 'nullable|string|max:100',
            'subscription_type' => 'required|in:monthly,yearly',
            'status'            => 'required|in:pending,completed,failed,refunded',
            'payment_proof'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $validated['transaction_ref'] = $request->transaction_ref ?? ('MANUAL-' . strtoupper(Str::random(10)));

        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $name = time() . '_proof.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/payment_proofs'), $name);
            $validated['payment_proof'] = $name;
        }

        TenantPayment::create($validated);

        // If payment is completed and for a tenant, extend expiry
        if ($validated['status'] === 'completed') {
            $tenant = Tenant::find($validated['tenant_id']);
            if ($tenant) {
                $days = $validated['subscription_type'] === 'yearly' ? 365 : 30;
                $current = $tenant->expiry_date ?? now();
                $tenant->update(['expiry_date' => $current->addDays($days), 'status' => 1]);
            }
        }

        return back()->with('message', 'Payment recorded successfully.');
    }

    public function updateStatus(Request $request, TenantPayment $payment)
    {
        $request->validate(['status' => 'required|in:pending,completed,failed,refunded']);
        $payment->update(['status' => $request->status]);

        // Auto-extend expiry when approving a payment
        if ($request->status === 'completed') {
            $tenant = Tenant::find($payment->tenant_id);
            if ($tenant) {
                $days = $payment->subscription_type === 'yearly' ? 365 : 30;
                $current = $tenant->expiry_date ?? now();
                $tenant->update(['expiry_date' => $current->addDays($days), 'status' => 1]);
            }
        }

        return back()->with('message', 'Payment status updated.');
    }

    public function destroy(TenantPayment $payment)
    {
        $payment->delete();
        return back()->with('message', 'Payment record deleted.');
    }
}
