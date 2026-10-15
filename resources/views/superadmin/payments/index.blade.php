@extends('superadmin.layout.main')
@section('title', 'Payments')
@section('page-title', 'Payment History')

@section('content')
<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#10b981,#059669);">
            <div class="stat-label">Total Revenue (Completed)</div>
            <div class="stat-value">$\{{ number_format($totalRevenue, 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <div class="stat-label">Pending Payments</div>
            <div class="stat-value">{{ $pendingCount }}</div>
        </div>
    </div>
</div>

<!-- Record Manual Payment -->
<div class="card mb-4">
    <div class="card-header py-3" data-bs-toggle="collapse" data-bs-target="#manualPayment" style="cursor:pointer;">
        <i class="fas fa-plus-circle me-2 text-success"></i>Record Manual Payment
        <i class="fas fa-chevron-down float-end mt-1"></i>
    </div>
    <div class="collapse" id="manualPayment">
        <div class="card-body">
            <form action="{{ route('superadmin.payments.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tenant</label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">Select…</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}">{{ $t->company_name ?? $t->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Amount</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency" class="form-control" value="USD" maxlength="3">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Plan</label>
                        <select name="subscription_type" class="form-select" required>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Paid By</label>
                        <input type="text" name="paid_by" class="form-control" placeholder="Payer name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Proof of Payment</label>
                        <input type="file" name="payment_proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-save me-1"></i>Record Payment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="tenant_id" class="form-select form-select-sm">
                    <option value="">All Tenants</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id')==$t->id?'selected':'' }}>
                            {{ $t->company_name ?? $t->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
                    <option value="pending"   {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                    <option value="failed"    {{ request('status')=='failed'?'selected':'' }}>Failed</option>
                    <option value="refunded"  {{ request('status')=='refunded'?'selected':'' }}>Refunded</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">Filter</button>
                <a href="{{ route('superadmin.payments.index') }}" class="btn btn-sm btn-outline-secondary flex-fill">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tenant</th>
                        <th>Amount</th>
                        <th>Ref</th>
                        <th>Plan</th>
                        <th>Gateway</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td><small class="text-muted">{{ $payment->id }}</small></td>
                        <td>
                            <div>{{ $payment->tenant->company_name ?? $payment->tenant_id }}</div>
                            <small class="text-muted">{{ $payment->paid_by }}</small>
                        </td>
                        <td class="fw-semibold">{{ strtoupper($payment->currency ?? 'USD') }} {{ number_format($payment->amount, 2) }}</td>
                        <td><code style="font-size:.75rem;">{{ Str::limit($payment->transaction_ref, 18) }}</code></td>
                        <td>{{ ucfirst($payment->subscription_type) }}</td>
                        <td>{{ $payment->gateway ?? 'Manual' }}</td>
                        <td style="font-size:.8rem;">{{ $payment->created_at->format('d M Y') }}</td>
                        <td>
                            @php $sc = ['completed'=>'success','pending'=>'warning','failed'=>'danger','refunded'=>'secondary'][$payment->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $sc }}">{{ ucfirst($payment->status) }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                @if($payment->status === 'pending')
                                <form action="{{ route('superadmin.payments.update-status', $payment->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button class="btn btn-sm btn-success" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('superadmin.payments.destroy', $payment->id) }}" method="POST"
                                      onsubmit="return confirm('Delete payment record?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No payments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of {{ $payments->total() }}</small>
        {{ $payments->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
