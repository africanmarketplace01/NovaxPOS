@extends('superadmin.layout.main')
@section('title', 'Platform Fees')
@section('page-title', 'Marketplace Fee Configuration')

@section('content')

@if(session('message'))
<div class="alert alert-success">{{ session('message') }}</div>
@endif

{{-- Global fee --}}
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-globe me-2 text-primary"></i>Global Default Fee</h6>
        <div class="text-muted small mt-1">Added to the customer's total on items from <strong>subscribed</strong> merchants, unless a tenant-specific rule overrides it. Marketplace sellers pay the commission below instead.</div>
    </div>
    <div class="card-body">
        <form action="{{ route('superadmin.marketplace.fees.update') }}" method="POST">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Percent Fee (%)</label>
                    <div class="input-group">
                        <input type="number" name="global_percent" step="0.01" min="0" max="50"
                               class="form-control" value="{{ $globalFee->percent_fee }}">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Flat Fee ($)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="global_flat" step="0.01" min="0" max="999"
                               class="form-control" value="{{ $globalFee->flat_fee }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Label (shown to customer)</label>
                    <input type="text" name="global_label" class="form-control" value="{{ $globalFee->label }}" maxlength="80">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Save</button>
                </div>
            </div>
            <div class="mt-2 text-muted small">
                Example: 5% + $0.25 flat = on a $100 order, fee = <strong>$5.25</strong>
            </div>
        </form>
    </div>
</div>

{{-- Marketplace seller commission --}}
<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-store me-2 text-warning"></i>Marketplace Seller Commission</h6>
        <div class="text-muted small mt-1">
            Merchants on the free <strong>Marketplace Seller</strong> plan ({{ $sellerCount }} today) pay no subscription.
            Customers pay the listed price; the seller owes this commission on each marketplace order, billed through platform fee requests.
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('superadmin.marketplace.fees.seller') }}" method="POST">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Commission (%)</label>
                    <div class="input-group">
                        <input type="number" name="seller_percent" step="0.01" min="0" max="50" class="form-control" value="{{ $sellerCommission['percent'] }}">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Flat per order ($)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="seller_flat" step="0.01" min="0" max="999" class="form-control" value="{{ $sellerCommission['flat'] }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Fee invoice due after (days)</label>
                    <input type="number" name="seller_due_days" min="1" max="60" class="form-control" value="{{ $sellerCommission['due_days'] }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-warning w-100">Save</button>
                </div>
            </div>
            <div class="mt-2 text-muted small">
                Listings are hidden when a sent fee invoice is still unpaid this many days after sending, plus the platform grace days.
                They reappear as soon as it is marked paid or cancelled. A per-tenant override below replaces this rate for that seller.
            </div>
        </form>

        @if ($heldSellers->isNotEmpty())
            <div class="alert alert-warning mt-3 mb-0 small">
                <strong><i class="fas fa-eye-slash me-1"></i>Listings hidden for overdue fees:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($heldSellers as $held)
                        <li><code>{{ $held->id }}</code> {{ $held->company_name }} &middot; {{ $held->marketplace_hold_reason }} (since {{ optional($held->marketplace_hold_at)->format('M j') }})</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>

{{-- Per-tenant fees --}}
<div class="card mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-semibold"><i class="fas fa-user-tag me-2 text-success"></i>Per-Tenant Overrides</h6>
            <div class="text-muted small mt-1">Override the fee for a specific tenant (e.g. preferred partners). For a marketplace seller this replaces their commission rate.</div>
        </div>
        <button class="btn btn-sm btn-outline-success" onclick="document.getElementById('addTenantFee').classList.toggle('d-none')">
            <i class="fas fa-plus me-1"></i>Add Override
        </button>
    </div>
    <div class="card-body">
        {{-- Add form --}}
        <form action="{{ route('superadmin.marketplace.fees.tenant') }}" method="POST" id="addTenantFee" class="d-none mb-4 p-3 bg-light rounded-3">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Tenant</label>
                    <select name="tenant_id" class="form-select">
                        @foreach($tenants as $t)
                            <option value="{{ $t->id }}">{{ $t->id }}@if ($t->isMarketplaceSeller()) (marketplace seller)@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Percent Fee (%)</label>
                    <input type="number" name="percent_fee" class="form-control" step="0.01" min="0" max="50" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Flat Fee ($)</label>
                    <input type="number" name="flat_fee" class="form-control" step="0.01" min="0" value="0">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Add</button>
                </div>
            </div>
        </form>

        @if($tenantFees->isEmpty())
            <p class="text-muted small mb-0">No per-tenant overrides. All tenants use the global fee.</p>
        @else
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Tenant</th><th>Percent</th><th>Flat</th><th></th></tr></thead>
                <tbody>
                    @foreach($tenantFees as $f)
                    <tr>
                        <td><code>{{ $f->scope_id }}</code></td>
                        <td>{{ $f->percent_fee }}%</td>
                        <td>${{ number_format($f->flat_fee,2) }}</td>
                        <td>
                            <form action="{{ route('superadmin.marketplace.fees.delete', $f->id) }}" method="POST" onsubmit="return confirm('Remove?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
