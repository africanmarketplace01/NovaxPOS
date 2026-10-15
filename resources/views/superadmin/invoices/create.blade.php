@extends('superadmin.layout.main')
@section('title', 'Create Invoice')
@section('page-title', 'New Invoice')

@section('styles')
<style>
.inv-form-card { background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem; }
.line-item-row td { vertical-align:middle; }
.item-desc { width:40%; }
.item-num  { width:15%; }
.inv-total-row td { padding:.4rem .5rem;font-size:.9rem; }
</style>
@endsection

@section('content')
<form method="POST" action="{{ route('superadmin.invoices.store') }}" id="inv-form">
@csrf

<div class="row g-4">

    {{-- Left column --}}
    <div class="col-lg-8">

        {{-- Client --}}
        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-user me-2 text-primary"></i>Bill To</h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Client (Tenant) <span class="text-muted fw-normal">— optional</span></label>
                    <select name="tenant_id" class="form-select form-select-sm" id="tenant-picker">
                        <option value="">— Select tenant or fill manually —</option>
                        @foreach($tenants as $t)
                        <option value="{{ $t->id }}"
                                data-name="{{ $t->company_name }}"
                                data-email="{{ $t->email }}">
                            {{ $t->company_name }} ({{ $t->email }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Company Name</label>
                    <input type="text" name="tenant_company" id="tenant_company" class="form-control form-control-sm" placeholder="Company or trading name">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Contact Name <span class="text-danger">*</span></label>
                    <input type="text" name="tenant_name" id="tenant_name" class="form-control form-control-sm" required placeholder="Full name">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" style="font-size:.82rem;">Email</label>
                    <input type="email" name="tenant_email" id="tenant_email" class="form-control form-control-sm" placeholder="client@example.com">
                </div>
            </div>
        </div>

        {{-- Line Items --}}
        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-list me-2 text-primary"></i>Line Items</h6>
            <div class="table-responsive">
                <table class="table table-sm mb-2" id="line-items-table">
                    <thead class="table-light">
                        <tr>
                            <th class="item-desc">Description</th>
                            <th class="item-num">Qty</th>
                            <th class="item-num">Unit Price</th>
                            <th class="item-num">Amount</th>
                            <th style="width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="line-items-body">
                        <tr class="line-item-row">
                            <td><input type="text" name="items[0][description]" class="form-control form-control-sm" placeholder="Subscription – Pro Plan" required></td>
                            <td><input type="number" name="items[0][qty]" class="form-control form-control-sm item-qty" value="1" min="0.01" step="0.01" required></td>
                            <td><input type="number" name="items[0][unit_price]" class="form-control form-control-sm item-price" value="0.00" min="0" step="0.01" required></td>
                            <td><input type="text" class="form-control form-control-sm item-amount bg-light" value="0.00" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Remove"><i class="fas fa-times"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" id="add-line" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-plus me-1"></i>Add Line
            </button>
        </div>

        {{-- Notes --}}
        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-sticky-note me-2 text-primary"></i>Notes</h6>
            <textarea name="notes" class="form-control form-control-sm" rows="3" placeholder="Payment instructions, thank-you message, or any additional info…"></textarea>
        </div>

    </div>

    {{-- Right column --}}
    <div class="col-lg-4">

        {{-- Invoice Details --}}
        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-file-invoice me-2 text-primary"></i>Invoice Details</h6>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.82rem;">Invoice Date <span class="text-danger">*</span></label>
                <input type="date" name="invoice_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.82rem;">Due Date</label>
                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.82rem;">Currency</label>
                <select name="currency" class="form-select form-select-sm">
                    <option value="USD" selected>USD – US Dollar</option>
                    <option value="EUR">EUR – Euro</option>
                    <option value="GBP">GBP – British Pound</option>
                    <option value="NGN">NGN – Nigerian Naira</option>
                    <option value="GHS">GHS – Ghanaian Cedi</option>
                    <option value="KES">KES – Kenyan Shilling</option>
                    <option value="ZAR">ZAR – South African Rand</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:.82rem;">Reference / PO #</label>
                <input type="text" name="reference" class="form-control form-control-sm" placeholder="Optional reference">
            </div>
        </div>

        {{-- Totals --}}
        <div class="inv-form-card">
            <h6 class="fw-bold mb-3"><i class="fas fa-calculator me-2 text-primary"></i>Totals</h6>
            <table class="table table-sm mb-3">
                <tbody class="inv-total-row">
                    <tr>
                        <td class="text-muted">Subtotal</td>
                        <td class="text-end fw-semibold" id="disp-subtotal">0.00</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted">Tax</span>
                                <div class="input-group input-group-sm" style="width:90px;">
                                    <input type="number" name="tax_rate" id="tax-rate" class="form-control form-control-sm" value="0" min="0" max="100" step="0.1">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-end" id="disp-tax">0.00</td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted">Discount</span>
                                <input type="number" name="discount" id="discount" class="form-control form-control-sm" value="0" min="0" step="0.01" style="width:90px;">
                            </div>
                        </td>
                        <td class="text-end text-danger" id="disp-discount">-0.00</td>
                    </tr>
                    <tr class="fw-bold border-top">
                        <td>Total</td>
                        <td class="text-end" id="disp-total">0.00</td>
                    </tr>
                </tbody>
            </table>

            <button type="submit" class="btn btn-success w-100 fw-bold">
                <i class="fas fa-save me-2"></i>Create Invoice
            </button>
            <a href="{{ route('superadmin.invoices.index') }}" class="btn btn-light w-100 mt-2">Cancel</a>
        </div>

    </div>
</div>
</form>
@endsection

@section('scripts')
<script>
// ── Auto-fill tenant fields on picker change ────────────────────────────────
document.getElementById('tenant-picker').addEventListener('change', function(){
    var opt = this.options[this.selectedIndex];
    document.getElementById('tenant_name').value    = opt.dataset.name  || '';
    document.getElementById('tenant_company').value = opt.dataset.name  || '';
    document.getElementById('tenant_email').value   = opt.dataset.email || '';
});

// ── Line item math ───────────────────────────────────────────────────────────
var lineIndex = 1;

function recalcRow(row) {
    var qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
    var price = parseFloat(row.querySelector('.item-price').value) || 0;
    row.querySelector('.item-amount').value = (qty * price).toFixed(2);
    recalcTotals();
}

function recalcTotals() {
    var subtotal = 0;
    document.querySelectorAll('.item-amount').forEach(function(el){
        subtotal += parseFloat(el.value) || 0;
    });
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
        <td><input type="text" name="items[${lineIndex}][description]" class="form-control form-control-sm" placeholder="Item description" required></td>
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
    var rows = document.querySelectorAll('.line-item-row');
    if (rows.length <= 1) return;
    btn.closest('.line-item-row').remove();
    recalcTotals();
});
</script>
@endsection
