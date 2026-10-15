@extends('store.layout')
@section('title', 'Order Confirmed — ' . ($store->store_name ?? 'Store'))

@section('content')
<div class="container" style="padding:4rem 0;max-width:600px;text-align:center">
    <div style="background:#fff;border-radius:16px;border:1px solid var(--store-border);padding:3rem 2rem">
        <div style="width:80px;height:80px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;color:#16a34a">
            <i class="fas fa-check"></i>
        </div>
        <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:.5rem">Order Confirmed!</h1>
        <p style="color:#64748b;margin-bottom:1.5rem">Thank you, <strong>{{ $order->customer_name }}</strong>. Your order has been received.</p>

        <div style="background:#f8fafc;border-radius:10px;padding:1.25rem;margin-bottom:1.5rem;text-align:left">
            <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                <span style="font-size:.85rem;color:#64748b">Order Number</span>
                <span style="font-weight:700;font-family:monospace;color:var(--store-primary)">{{ $order->order_number }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                <span style="font-size:.85rem;color:#64748b">Total</span>
                <span style="font-weight:700">{{ number_format($order->total, 2) }}</span>
            </div>
            <div style="display:flex;justify-content:space-between">
                <span style="font-size:.85rem;color:#64748b">Status</span>
                <span style="background:#fef3c7;color:#92400e;font-size:.75rem;font-weight:700;padding:.2em .6em;border-radius:999px;text-transform:uppercase">
                    {{ $order->status }}
                </span>
            </div>
        </div>

        @if($order->customer_email)
        <p style="font-size:.85rem;color:#64748b;margin-bottom:1.5rem">
            A confirmation has been sent to <strong>{{ $order->customer_email }}</strong>.
        </p>
        @endif

        @if($store->whatsapp_number)
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $store->whatsapp_number) }}?text={{ urlencode('Hi! My order number is ' . $order->order_number . '. Can you confirm the status?') }}"
           target="_blank" class="btn mb-3"
           style="background:#25D366;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.9rem;padding:.55rem 1.5rem">
            <i class="fab fa-whatsapp me-2"></i> Chat on WhatsApp
        </a>
        <br>
        @endif

        <a href="{{ route('store.products') }}" class="btn btn-store-primary px-5 mt-2">
            <i class="fas fa-shopping-bag me-2"></i> Continue Shopping
        </a>
    </div>
</div>
@endsection
