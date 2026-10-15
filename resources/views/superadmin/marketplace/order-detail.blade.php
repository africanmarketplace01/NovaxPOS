@extends('superadmin.layout.main')
@section('title', 'Order ' . $order->order_number)
@section('page-title', 'Marketplace Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-semibold">Order {{ $order->order_number }}</h5>
        <small class="text-muted">Placed {{ $order->created_at->format('d M Y, H:i') }} ({{ $order->created_at->diffForHumans() }})</small>
    </div>
    <a href="{{ route('superadmin.marketplace.orders') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Orders
    </a>
</div>

<div class="row g-3">
    {{-- Left: items --}}
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Items</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Item</th><th>Vendor</th><th class="text-center">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item['name'] ?? '—' }}</td>
                            <td><small class="text-muted">{{ $item['tenant_store_name'] ?? $item['tenant_id'] ?? '—' }}</small></td>
                            <td class="text-center">{{ $item['qty'] ?? 1 }}</td>
                            <td class="text-end">{{ number_format($item['price'] ?? 0, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item['subtotal'] ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="4" class="text-end text-muted">Subtotal</td><td class="text-end">{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Platform Fee</td><td class="text-end">{{ $order->currency }} {{ number_format($order->platform_fee, 2) }}</td></tr>
                        <tr><td colspan="4" class="text-end text-muted">Delivery Fee</td><td class="text-end">{{ $order->currency }} {{ number_format($order->delivery_fee, 2) }}</td></tr>
                        <tr><td colspan="4" class="text-end fw-bold">Total</td><td class="text-end fw-bold">{{ $order->currency }} {{ number_format($order->total, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($order->note)
        <div class="alert alert-warning"><strong>Customer note:</strong> {{ $order->note }}</div>
        @endif
    </div>

    {{-- Right: customer + status --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Customer</div>
            <div class="card-body">
                <p class="mb-1 fw-semibold">{{ $order->customer_name }}</p>
                <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i>{{ $order->customer_phone }}</p>
                @if($order->customer_email)
                <p class="mb-1"><i class="fas fa-envelope me-2 text-muted"></i>{{ $order->customer_email }}</p>
                @endif
                <p class="mb-0"><i class="fas fa-map-marker-alt me-2 text-muted"></i>{{ $order->customer_address }}</p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Delivery</div>
            <div class="card-body">
                <p class="mb-1">{{ $order->delivery_agency_name ?? 'Customer pickup / no agency' }}</p>
                @if($order->estimated_delivery)
                <p class="mb-0 text-muted"><small>Estimate: {{ $order->estimated_delivery }}</small></p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Status</div>
            <div class="card-body">
                <form action="{{ route('superadmin.marketplace.orders.status', $order->id) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small text-muted">Order Status</label>
                        <select name="status" class="form-select form-select-sm">
                            @foreach(['pending','processing','shipped','delivered','cancelled'] as $st)
                                <option value="{{ $st }}" {{ $order->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Payment Status ({{ strtoupper($order->payment_method) }})</label>
                        <select name="payment_status" class="form-select form-select-sm">
                            @foreach(['pending','paid','failed','refunded'] as $ps)
                                <option value="{{ $ps }}" {{ $order->payment_status === $ps ? 'selected' : '' }}>{{ ucfirst($ps) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm w-100">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
