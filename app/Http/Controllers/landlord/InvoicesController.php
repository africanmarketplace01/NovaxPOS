<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\LandlordInvoice;
use App\Models\landlord\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoicesController extends Controller
{
    // ── List ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = LandlordInvoice::with('tenant')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%$s%")
                  ->orWhere('tenant_name', 'like', "%$s%")
                  ->orWhere('tenant_company', 'like', "%$s%")
                  ->orWhere('tenant_email', 'like', "%$s%");
            });
        }

        $invoices = $query->paginate(20)->withQueryString();

        $stats = [
            'total'     => LandlordInvoice::count(),
            'unpaid'    => LandlordInvoice::unpaid()->count(),
            'paid'      => LandlordInvoice::paid()->count(),
            'overdue'   => LandlordInvoice::overdue()->count(),
            'revenue'   => LandlordInvoice::paid()->sum('total'),
            'outstanding' => LandlordInvoice::unpaid()->sum('total'),
        ];

        return view('superadmin.invoices.index', compact('invoices', 'stats'));
    }

    // ── Create Form ───────────────────────────────────────────────────────────
    public function create()
    {
        $tenants = Tenant::active()->get(['id', 'company_name', 'email']);
        return view('superadmin.invoices.create', compact('tenants'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'      => 'nullable|string',
            'tenant_name'    => 'required|string|max:200',
            'tenant_email'   => 'nullable|email|max:200',
            'tenant_company' => 'nullable|string|max:200',
            'invoice_date'   => 'required|date',
            'due_date'       => 'nullable|date|after_or_equal:invoice_date',
            'currency'       => 'required|string|max:10',
            'tax_rate'       => 'nullable|numeric|min:0|max:100',
            'discount'       => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:1000',
            'reference'      => 'nullable|string|max:100',
            'items'          => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.qty'         => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $lineItems = [];
        $subtotal  = 0;
        foreach ($request->items as $item) {
            $amount     = (float)$item['qty'] * (float)$item['unit_price'];
            $subtotal  += $amount;
            $lineItems[] = [
                'description' => $item['description'],
                'qty'         => (float)$item['qty'],
                'unit_price'  => (float)$item['unit_price'],
                'amount'      => $amount,
            ];
        }

        $taxRate   = (float)($request->tax_rate ?? 0);
        $discount  = (float)($request->discount ?? 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total     = $subtotal + $taxAmount - $discount;

        LandlordInvoice::create([
            'invoice_number' => LandlordInvoice::generateNumber(),
            'tenant_id'      => $request->tenant_id,
            'tenant_name'    => $request->tenant_name,
            'tenant_email'   => $request->tenant_email,
            'tenant_company' => $request->tenant_company,
            'invoice_date'   => $request->invoice_date,
            'due_date'       => $request->due_date,
            'currency'       => $request->currency,
            'subtotal'       => $subtotal,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmount,
            'discount'       => $discount,
            'total'          => $total,
            'notes'          => $request->notes,
            'reference'      => $request->reference,
            'line_items'     => $lineItems,
            'status'         => 'unpaid',
            'amount_paid'    => 0,
        ]);

        return redirect()->route('superadmin.invoices.index')
                         ->with('success', 'Invoice created successfully.');
    }

    // ── Show / Detail ─────────────────────────────────────────────────────────
    public function show(LandlordInvoice $invoice)
    {
        return view('superadmin.invoices.show', compact('invoice'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────
    public function edit(LandlordInvoice $invoice)
    {
        $tenants = Tenant::active()->get(['id', 'company_name', 'email']);
        return view('superadmin.invoices.edit', compact('invoice', 'tenants'));
    }

    // ── Update ────────────────────────────────────────────────────────────────
    public function update(Request $request, LandlordInvoice $invoice)
    {
        $request->validate([
            'tenant_name'    => 'required|string|max:200',
            'tenant_email'   => 'nullable|email|max:200',
            'invoice_date'   => 'required|date',
            'due_date'       => 'nullable|date',
            'currency'       => 'required|string|max:10',
            'tax_rate'       => 'nullable|numeric|min:0|max:100',
            'discount'       => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:1000',
            'items'          => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.qty'         => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $lineItems = [];
        $subtotal  = 0;
        foreach ($request->items as $item) {
            $amount     = (float)$item['qty'] * (float)$item['unit_price'];
            $subtotal  += $amount;
            $lineItems[] = [
                'description' => $item['description'],
                'qty'         => (float)$item['qty'],
                'unit_price'  => (float)$item['unit_price'],
                'amount'      => $amount,
            ];
        }

        $taxRate   = (float)($request->tax_rate ?? 0);
        $discount  = (float)($request->discount ?? 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total     = $subtotal + $taxAmount - $discount;

        $invoice->update([
            'tenant_id'      => $request->tenant_id,
            'tenant_name'    => $request->tenant_name,
            'tenant_email'   => $request->tenant_email,
            'tenant_company' => $request->tenant_company,
            'invoice_date'   => $request->invoice_date,
            'due_date'       => $request->due_date,
            'currency'       => $request->currency,
            'subtotal'       => $subtotal,
            'tax_rate'       => $taxRate,
            'tax_amount'     => $taxAmount,
            'discount'       => $discount,
            'total'          => $total,
            'notes'          => $request->notes,
            'reference'      => $request->reference,
            'line_items'     => $lineItems,
        ]);

        return redirect()->route('superadmin.invoices.show', $invoice)
                         ->with('success', 'Invoice updated.');
    }

    // ── Record Payment ────────────────────────────────────────────────────────
    public function recordPayment(Request $request, LandlordInvoice $invoice)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'paid_date'      => 'required|date',
        ]);

        $newPaid = (float)$invoice->amount_paid + (float)$request->amount;
        $newPaid = min($newPaid, (float)$invoice->total);

        $status = match (true) {
            $newPaid >= (float)$invoice->total => 'paid',
            $newPaid > 0                       => 'partial',
            default                            => 'unpaid',
        };

        $invoice->update([
            'amount_paid'    => $newPaid,
            'status'         => $status,
            'payment_method' => $request->payment_method,
            'paid_date'      => $status === 'paid' ? $request->paid_date : $invoice->paid_date,
        ]);

        return back()->with('success', 'Payment recorded.');
    }

    // ── Update Status ─────────────────────────────────────────────────────────
    public function updateStatus(Request $request, LandlordInvoice $invoice)
    {
        $request->validate(['status' => 'required|in:unpaid,paid,partial,cancelled']);
        $invoice->update(['status' => $request->status]);
        return back()->with('success', 'Status updated.');
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    public function destroy(LandlordInvoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('superadmin.invoices.index')
                         ->with('success', 'Invoice deleted.');
    }

    // ── PDF Print View ────────────────────────────────────────────────────────
    public function printInvoice(LandlordInvoice $invoice)
    {
        return view('superadmin.invoices.print', compact('invoice'));
    }
}
