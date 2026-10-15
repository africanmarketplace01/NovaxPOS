@extends('superadmin.layout.main')
@section('title', 'Marketplace Orders')
@section('page-title', 'Marketplace Orders')

@section('content')

{{-- Summary strip --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold text-success">${{ number_format($summary['total_platform_fees'],2) }}</div>
            <div class="text-muted small">Total Platform Revenue</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold">${{ number_format($summary['total_sales'],2) }}</div>
            <div class="text-muted small">Total Gross Sales</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center py-3">
            <div class="fs-4 fw-bold text-warning">{{ $summary['pending_count'] }}</div>
            <div class="text-muted small">Pending Orders</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('superadmin.marketplace.orders') }}" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Order # / customer…" value="{{ request('q') }}">
    </div>
    <div class="col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status')===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="payment_status" class="form-select form-select-sm">
            <option value="">All payments</option>
            @foreach(['pending','paid','failed','refunded'] as $p)
            <option value="{{ $p }}" {{ request('payment_status')===$p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-primary">Filter</button>
        <a href="{{ route('superadmin.marketplace.orders') }}" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order #</th><th>Customer</th><th>Subtotal</th><th>Platform Fee</th><th>Delivery</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                <tr>
                    <td><span class="badge bg-light text-dark border font-monospace" style="font-size:.75rem">{{ $o->order_number }}</span></td>
                    <td>
                        <div class="fw-semibold small">{{ $o->customer_name }}</div>
                        <small class="text-muted">{{ $o->customer_email }}</small>
                    </td>
                    <td class="small">{{ number_format($o->subtotal,2) }}</td>
                    <td class="text-success fw-semibold small">+{{ number_format($o->platform_fee,2) }}</td>
                    <td class="small">{{ number_format($o->delivery_fee,2) }}</td>
                    <td class="fw-bold small">{{ $o->currency }} {{ number_format($o->total,2) }}</td>
                    <td>
                        <span class="badge bg-{{ $o->paymentBadge() }}">{{ ucfirst($o->payment_status) }}</span><br>
                        <small class="text-muted">{{ ucwords(str_replace('_',' ',$o->payment_method)) }}</small>
                    </td>
                    <td>
                        <select class="form-select form-select-sm status-select" data-id="{{ $o->id }}" style="min-width:110px">
                            @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                            <option value="{{ $s }}" {{ $o->status===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-muted small">{{ $o->created_at->format('d M H:i') }}</td>
                    <td>
                        <a href="{{ route('superadmin.marketplace.order.show', $o->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center py-4 text-muted">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $orders->withQueryString()->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('change', function() {
        fetch(`/superadmin/marketplace/orders/${this.dataset.id}/status`, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
            body: JSON.stringify({ status: this.value })
        }).then(r => r.json()).then(d => { if(!d.ok) alert('Update failed'); });
    });
});
</script>
@endpush
