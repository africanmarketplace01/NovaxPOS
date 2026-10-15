@extends('landlord.marketplace.layout')
@section('title', 'Order Confirmed')

@section('content')
<div class="container py-5" style="max-width:680px">
    <div class="text-center mb-4">
        <div style="width:80px;height:80px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;border:2px solid #22c55e">
            <i class="fas fa-check" style="font-size:2rem;color:#22c55e"></i>
        </div>
        <h1 class="fw-bold" style="font-size:1.8rem">Order Confirmed!</h1>
        <p class="text-muted">Thank you, {{ $order->customer_name }}. We've received your order.</p>
        <div class="badge bg-light text-dark border px-3 py-2" style="font-size:var(--text-sm);letter-spacing:.05em">
            {{ $order->order_number }}
        </div>
        <div class="mt-3">
            @auth('customer')
                <a href="{{ route('customer.orders.show', $order->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-truck-fast me-1"></i> Track your order</a>
            @else
                <a href="{{ route('customer.login') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-user me-1"></i> Sign in to track this order</a>
            @endauth
        </div>
    </div>

    {{-- Tracking timeline --}}
    <div class="bg-white rounded-3 p-4 mb-4" style="border:1px solid var(--mp-border)">
        <h6 class="fw-bold mb-3"><i class="fas fa-truck-fast me-2" style="color: var(--mp-link)"></i>Order Tracking</h6>
        @include('landlord.marketplace._tracking', ['order' => $order])
    </div>

    <div class="bg-white rounded-3 p-4 mb-4" style="border:1px solid var(--mp-border)">
        <h6 class="fw-bold mb-3">Order Details</h6>

        @foreach($order->items as $item)
        <div class="d-flex justify-content-between small py-1" style="border-bottom:1px solid #f1f5f9">
            <span>{{ $item['name'] }} <span class="text-muted">×{{ $item['qty'] }}</span></span>
            <span>{{ number_format($item['subtotal'],2) }}</span>
        </div>
        @endforeach

        <div class="mt-3 pt-2">
            <div class="d-flex justify-content-between small mb-1">
                <span>Subtotal</span><span>{{ number_format($order->subtotal,2) }}</span>
            </div>
            @if($order->platform_fee > 0)
            <div class="d-flex justify-content-between small mb-1 text-muted">
                <span>Platform fee</span><span>{{ number_format($order->platform_fee,2) }}</span>
            </div>
            @endif
            @if($order->delivery_fee > 0)
            <div class="d-flex justify-content-between small mb-1">
                <span>Delivery ({{ $order->delivery_agency_name }})</span><span>{{ number_format($order->delivery_fee,2) }}</span>
            </div>
            @endif
            <div class="d-flex justify-content-between fw-bold mt-2" style="font-size:var(--text-base)">
                <span>Total</span><span style="color: var(--mp-link)">{{ $order->currency }} {{ number_format($order->total,2) }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3 p-4 mb-4" style="border:1px solid var(--mp-border)">
        <h6 class="fw-bold mb-3">Delivery Info</h6>
        <div class="small">
            <div><i class="fas fa-map-marker-alt me-2" style="color: var(--mp-link)"></i>{{ $order->customer_address }}</div>
            @if($order->delivery_agency_name)
            <div class="mt-1"><i class="fas fa-truck me-2" style="color: var(--mp-link)"></i>Delivered by <strong>{{ $order->delivery_agency_name }}</strong>
                @if($order->estimated_delivery) · {{ $order->estimated_delivery }} @endif
            </div>
            @endif
            <div class="mt-1"><i class="fas fa-credit-card me-2" style="color: var(--mp-link)"></i>
                Payment: <strong>{{ ucwords(str_replace('_',' ',$order->payment_method)) }}</strong>
                <span class="badge {{ $order->payment_status==='paid' ? 'bg-success' : 'bg-warning text-dark' }} ms-1">{{ ucfirst($order->payment_status) }}</span>
            </div>
        </div>
    </div>

    <div class="text-center">
        <a href="{{ route('marketplace.index') }}" class="btn-mp" style="padding:.65rem 2rem">
            Continue Shopping
        </a>
    </div>
</div>
@endsection
