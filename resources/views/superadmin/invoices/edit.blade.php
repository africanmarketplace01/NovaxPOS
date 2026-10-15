@extends('superadmin.layout.main')
@section('title', 'Edit Invoice')
@section('page-title', 'Edit Invoice — ' . $invoice->invoice_number)

@section('styles')
<style>
.inv-form-card { background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem; }
.inv-total-row td { padding:.4rem .5rem;font-size:.9rem; }
</style>
@endsection

@section('content')
<form method="POST" action="{{ route('superadmin.invoices.update', $invoice) }}" id="inv-form">
@csrf @method('PUT')

<div class="row g-4">

    <div class="col-lg-8">
        {{-- Client --}}
        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-user me-2 text-primary"></i>Bill To</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Client (Tenant)</label>
                    <select name="tenant_id" class="form-select form-select-sm" id="tenant-picker">
                        <option value="">— Select tenant or fill manually —</option>
                        @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ $invoice->tenant_id == $t->id ? 'selected' : '' }}
                                data-name="{{ $t->company_name }}" data-email="{{ $t->email }}">
                            {{ $t->company_name }} ({{ $t->email }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Company Name</label>
                    <input type="text" name="tenant_company" id="tenant_company" class="form-control form-control-sm" value="{{ $invoice->tenant_company }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Contact Name <span class="text-danger">*</span></label>
                    <input type="text" name="tenant_name" id="tenant_name" class="form-control form-control-sm" value="{{ $invoice->tenant_name }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Email</label>
                    <input type="email" name="tenant_email" id="tenant_email" class="form-control form-control-sm" value="{{ $invoice->tenant_email }}">
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-list me-2 text-primary"></i>Line Items</h6>
            <div class="table-responsive">
                <table class="table table-sm mb-2">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40%">Description</th>
                            <th style="width:15%">Qty</th>
                            <th style="width:15%">Unit Price</th>
                            <th style="width:15%">Amount</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="line-items-body">
                        @foreach($invoice->line_items ?? [] as $i => $item)
                        <tr class="line-item-row">
                            <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $item['description'] }}" required></td>
                            <td><input type="number" name="items[{{ $i }}][qty]" class="form-control form-control-sm item-qty" value="{{ $item['qty'] }}" min="0.01" step="0.01" required></td>
                            <td><input type="number" name="items[{{ $i }}][unit_price]" class="form-control form-control-sm item-price" value="{{ $item['unit_price'] }}" min="0" step="0.01" required></td>
                            <td><input type="text" class="form-control form-control-sm item-amount bg-light" value="{{ $item['amount'] }}" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="fas fa-times"></i></button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" id="add-line" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus me-1"></i>Add Line
            </button>
        </div>

        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-sticky-note me-2 text-primary"></i>Notes</h6>
            <textarea name="notes" class="form-control form-control-sm" rows="3">{{ $invoice->notes }}</textarea>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-file-invoice me-2 text-primary"></i>Invoice Details</h6>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.82rem;">Invoice Date <span class="text-danger">*</span></label>
                <input type="date" name="invoice_date" class="form-control form-control-sm" value="{{ $invoice->invoice_date->format('Y-m-d') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.82rem;">Due Date</label>
                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ $invoice->due_date?->format('Y-m-d') }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.82rem;">Currency</label>
                <select name="currency" class="form-select form-select-sm">
                    @foreach(['USD','EUR','GBP','NGN','GHS','KES','ZAR'] as $cur)
                    <option value="{{ $cur }}" {{ $invoice->currency == $cur ? 'selected' : '' }}>{{ $cur }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.82rem;">Reference</label>
                <input type="text" name="reference" class="form-control form-control-sm" value="{{ $invoice->reference }}">
            </div>
        </div>

        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-calculator me-2 text-primary"></i>Totals</h6>
            <table class="table table-sm mb-3">
                <tbody class="inv-total-row">
                    <tr><td class="text-muted">Subtotal</td><td class="text-end fw-semibold" id="disp-subtotal">{{ number_format($invoice->subtotal, 2) }}</td></tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted">Tax</span>
                                <div class="input-group input-group-sm" style="width:90px;">
                                    <input type="number" name="tax_rate" id="tax-rate" class="form-control form-control-sm" value="{{ $invoice->tax_rate }}" min="0" max="100" step="0.1">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-end" id="disp-tax">{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted">Discount</span>
                                <input type="number" name="discount" id="discount" class="form-control form-control-sm" value="{{ $invoice->discount }}" min="0" step="0.01" style="width:90px;">
                            </div>
                        </td>
                        <td class="text-end text-danger" id="disp-discount">-{{ number_format($invoice->discount, 2) }}</td>
                    </tr>
                    <tr class="fw-bold border-top">
                        <td>Total</td>
                        <td class="text-end" id="disp-total">{{ number_format($invoice->total, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="btn btn-warning w-100 fw-bold text-dark">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
            <a href="{{ route('superadmin.invoices.show', $invoice) }}" class="btn btn-light w-100 mt-2">Cancel</a>
        </div>
    </div>

</div>
</form>
@endsection

@section('scripts')
<script>
var lineIndex = {{ count($invoice->line_items ?? []) }};

function recalcRow(row) {
    var qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
    var price = parseFloat(row.querySelector('.item-price').value) || 0;
    row.querySelector('.item-amount').value = (qty * price).toFixed(2);
    recalcTotals();
}

function recalcTotals() {
    var subtotal = 0;
    document.querySelectorAll('.item-amount').forEach(function(el){ subtotal += parseFloat(el.value) || 0; });
    var tax      = subtotal * ((parseFloat(document.getElementById('tax-rate').value) || 0) / 100);
    var discount = parseFloat(document.getElementById('discount').value) || 0;
    var total    = subtotal + tax - discount;
    document.getElementById('disp-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('disp-tax').textContent      = tax.toFixed(2);
    document.getElementById('disp-discount').textContent = '-' + discount.toFixed(2);
    document.getElementById('disp-total').textContent    = total.toFixed(2);
}

document.getElementById('line-items-body').addEventListener('input', function(e){
    var row = e.target.closest('.line-item-row');
    if (row) recalcRow(row);
});
document.getElementById('tax-rate').addEventListener('input', recalcTotals);
document.getElementById('discount').addEventListener('input', recalcTotals);

document.getElementById('add-line').addEventListener('click', function(){
    var tbody = document.getElementById('line-items-body');
    var row   = document.createElement('tr');
    row.className = 'line-item-row';
    row.innerHTML = `
        <td><input type="text" name="items[${lineIndex}][description]" class="form-control form-control-sm" required></td>
        <td><input type="number" name="items[${lineIndex}][qty]" class="form-control form-control-sm item-qty" value="1" min="0.01" step="0.01" required></td>
        <td><input type="number" name="items[${lineIndex}][unit_price]" class="form-control form-control-sm item-price" value="0.00" min="0" step="0.01" required></td>
        <td><input type="text" class="form-control form-control-sm item-amount bg-light" value="0.00" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(row);
    lineIndex++;
});

document.getElementById('line-items-body').addEventListener('click', function(e){
    var btn = e.target.closest('.remove-line');
    if (!btn) return;
    if (document.querySelectorAll('.line-item-row').length <= 1) return;
    btn.closest('.line-item-row').remove();
    recalcTotals();
});
</script>
@endsection
