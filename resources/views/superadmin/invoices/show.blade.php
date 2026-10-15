@extends('superadmin.layout.main')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page-title', 'Invoice Detail')

@section('styles')
<style>
.inv-detail-card { background:#fff;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:1.25rem; }
.inv-header      { background:linear-gradient(135deg,var(--lp-dark,#003366),var(--lp-primary,#0066CC));color:#fff;border-radius:12px 12px 0 0;padding:2rem; }
.status-unpaid   { background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:700; }
.status-paid     { background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:700; }
.status-partial  { background:#fffbeb;color:#d97706;border:1px solid #fde68a;padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:700; }
.status-cancelled{ background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;padding:5px 14px;border-radius:20px;font-size:.8rem;font-weight:700; }
.inv-table th    { background:#f8f9fc;font-weight:600;font-size:.82rem;padding:.65rem 1rem;border-bottom:2px solid #e2e8f0; }
.inv-table td    { padding:.65rem 1rem;font-size:.88rem;border-bottom:1px solid #f1f2f6; }
</style>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- Main Invoice --}}
    <div class="col-lg-8">
        <div class="inv-detail-card">

            {{-- Header Banner --}}
            <div class="inv-header">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <div style="font-size:.75rem;opacity:.7;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;">Invoice</div>
                        <div style="font-size:1.8rem;font-weight:800;letter-spacing:-.02em;">{{ $invoice->invoice_number }}</div>
                        @if($invoice->reference)
                        <div style="font-size:.82rem;opacity:.7;margin-top:2px;">Ref: {{ $invoice->reference }}</div>
                        @endif
                    </div>
                    <div class="text-end">
                        <span class="status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                        @if($invoice->isOverdue())
                        <div style="background:#fee2e2;color:#b91c1c;padding:4px 10px;border-radius:6px;font-size:.72rem;font-weight:700;margin-top:6px;display:inline-block;">OVERDUE</div>
                        @endif
                    </div>
                </div>
                <div class="row mt-4 g-3">
                    <div class="col-sm-4">
                        <div style="opacity:.6;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;">Issue Date</div>
                        <div style="font-weight:600;font-size:.9rem;">{{ $invoice->invoice_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div style="opacity:.6;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;">Due Date</div>
                        <div style="font-weight:600;font-size:.9rem;">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</div>
                    </div>
                    <div class="col-sm-4">
                        <div style="opacity:.6;font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;">Currency</div>
                        <div style="font-weight:600;font-size:.9rem;">{{ $invoice->currency }}</div>
                    </div>
                </div>
            </div>

            {{-- Bill To / Bill From --}}
            <div class="p-3 border-bottom">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:.5rem;">Bill To</div>
                        <div class="fw-bold">{{ $invoice->tenant_company ?: $invoice->tenant_name }}</div>
                        @if($invoice->tenant_company)<div style="font-size:.85rem;color:#6b7280;">{{ $invoice->tenant_name }}</div>@endif
                        @if($invoice->tenant_email)<div style="font-size:.85rem;color:#6b7280;">{{ $invoice->tenant_email }}</div>@endif
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:.5rem;">From</div>
                        <div class="fw-bold">{{ config('app.name') }}</div>
                        <div style="font-size:.85rem;color:#6b7280;">SaaS Platform Administrator</div>
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="p-0">
                <table class="table inv-table mb-0">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->line_items ?? [] as $item)
                        <tr>
                            <td>{{ $item['description'] }}</td>
                            <td class="text-center">{{ number_format($item['qty'], 2) }}</td>
                            <td class="text-end">{{ $invoice->currency }} {{ number_format($item['unit_price'], 2) }}</td>
                            <td class="text-end fw-semibold">{{ $invoice->currency }} {{ number_format($item['amount'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end text-muted">Subtotal</td>
                            <td class="text-end fw-semibold">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if($invoice->tax_rate > 0)
                        <tr>
                            <td colspan="3" class="text-end text-muted">Tax ({{ $invoice->tax_rate }}%)</td>
                            <td class="text-end">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
                        </tr>
                        @endif
                        @if($invoice->discount > 0)
                        <tr>
                            <td colspan="3" class="text-end text-muted">Discount</td>
                            <td class="text-end text-danger">-{{ $invoice->currency }} {{ number_format($invoice->discount, 2) }}</td>
                        </tr>
                        @endif
                        <tr style="background:#f8f9fc;">
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold" style="font-size:1.05rem;">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
                        </tr>
                        @if($invoice->amount_paid > 0)
                        <tr>
                            <td colspan="3" class="text-end text-muted">Amount Paid</td>
                            <td class="text-end text-success fw-semibold">-{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</td>
                        </tr>
                        <tr style="background:#f0fdf4;">
                            <td colspan="3" class="text-end fw-bold">Balance Due</td>
                            <td class="text-end fw-bold text-{{ $invoice->balance_due > 0 ? 'danger' : 'success' }}" style="font-size:1.05rem;">
                                {{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}
                            </td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>

            @if($invoice->notes)
            <div class="p-3 border-top" style="background:#fafafa;border-radius:0 0 12px 12px;">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:.35rem;">Notes</div>
                <div style="font-size:.88rem;color:#374151;white-space:pre-line;">{{ $invoice->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Side Panel --}}
    <div class="col-lg-4">

        {{-- Quick Actions --}}
        <div class="inv-detail-card p-3 mb-3">
            <div class="fw-bold mb-3" style="font-size:.85rem;">Quick Actions</div>
            <div class="d-grid gap-2">
                <a href="{{ route('superadmin.invoices.print', $invoice) }}" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fas fa-print me-2"></i>Print / Export PDF
                </a>
                <a href="{{ route('superadmin.invoices.edit', $invoice) }}" class="btn btn-outline-warning btn-sm">
                    <i class="fas fa-edit me-2"></i>Edit Invoice
                </a>
                <a href="{{ route('superadmin.invoices.index') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Back to Invoices
                </a>
            </div>
        </div>

        {{-- Record Payment --}}
        @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
        <div class="inv-detail-card p-3 mb-3">
            <div class="fw-bold mb-3" style="font-size:.85rem;"><i class="fas fa-money-bill-wave me-2 text-success"></i>Record Payment</div>
            <form method="POST" action="{{ route('superadmin.invoices.payment', $invoice) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label" style="font-size:.78rem;font-weight:600;">Amount</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">{{ $invoice->currency }}</span>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                               max="{{ $invoice->balance_due }}" value="{{ number_format($invoice->balance_due, 2, '.', '') }}" required>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size:.78rem;font-weight:600;">Payment Method</label>
                    <select name="payment_method" class="form-select form-select-sm" required>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="cash">Cash</option>
                        <option value="paypal">PayPal</option>
                        <option value="stripe">Stripe</option>
                        <option value="paystack">Paystack</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:.78rem;font-weight:600;">Payment Date</label>
                    <input type="date" name="paid_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                </div>
                <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">
                    <i class="fas fa-check me-1"></i>Record Payment
                </button>
            </form>
        </div>
        @endif

        {{-- Status Update --}}
        <div class="inv-detail-card p-3 mb-3">
            <div class="fw-bold mb-3" style="font-size:.85rem;"><i class="fas fa-tag me-2 text-primary"></i>Update Status</div>
            <form method="POST" action="{{ route('superadmin.invoices.status', $invoice) }}">
                @csrf @method('PATCH')
                <div class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="unpaid"    {{ $invoice->status=='unpaid'    ?'selected':'' }}>Unpaid</option>
                        <option value="partial"   {{ $invoice->status=='partial'   ?'selected':'' }}>Partial</option>
                        <option value="paid"      {{ $invoice->status=='paid'      ?'selected':'' }}>Paid</option>
                        <option value="cancelled" {{ $invoice->status=='cancelled' ?'selected':'' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                </div>
            </form>
        </div>

        {{-- Summary --}}
        <div class="inv-detail-card p-3">
            <div class="fw-bold mb-3" style="font-size:.85rem;"><i class="fas fa-info-circle me-2 text-primary"></i>Summary</div>
            <table class="table table-sm mb-0">
                <tbody>
                    <tr><td class="text-muted" style="font-size:.82rem;">Invoice #</td><td class="fw-semibold" style="font-size:.82rem;">{{ $invoice->invoice_number }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.82rem;">Total</td><td class="fw-semibold" style="font-size:.82rem;">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.82rem;">Paid</td><td class="text-success fw-semibold" style="font-size:.82rem;">{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</td></tr>
                    <tr><td class="text-muted" style="font-size:.82rem;">Balance</td>
                        <td class="fw-bold {{ $invoice->balance_due > 0 ? 'text-danger' : 'text-success' }}" style="font-size:.82rem;">
                            {{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}
                        </td>
                    </tr>
                    @if($invoice->payment_method)
                    <tr><td class="text-muted" style="font-size:.82rem;">Method</td><td style="font-size:.82rem;">{{ ucfirst(str_replace('_',' ',$invoice->payment_method)) }}</td></tr>
                    @endif
                    <tr><td class="text-muted" style="font-size:.82rem;">Created</td><td style="font-size:.82rem;">{{ $invoice->created_at->format('d M Y') }}</td></tr>
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
