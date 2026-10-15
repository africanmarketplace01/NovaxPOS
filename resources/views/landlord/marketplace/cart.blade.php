@extends('landlord.marketplace.layout')
@section('title', 'Your Cart')

@section('content')
<div class="mp-breadcrumb">
    <div class="container">
        <a href="{{ route('marketplace.index') }}">Home</a> / Cart
    </div>
</div>

<div class="container py-4" style="max-width:820px">
    <h1 class="fw-bold mb-4" style="font-size:1.6rem">Your Cart</h1>

    @if(empty($cart))
        <div class="text-center py-5 text-muted">
            <i class="fas fa-shopping-cart fa-3x mb-3 d-block"></i>
            Your cart is empty.
            <a href="{{ route('marketplace.index') }}" class="btn-mp d-inline-block mt-3" style="padding:.5rem 1.5rem">Browse Products</a>
        </div>
    @else
    <div class="row g-4">
        <div class="col-lg-8">
            @foreach($cart as $key => $item)
            <div class="d-flex gap-3 align-items-start mb-3 p-3 bg-white rounded-3" style="border:1px solid var(--mp-border)">
                @if($item['image_url'])
                    <img src="{{ $item['image_url'] }}" style="width:70px;height:70px;object-fit:cover;border-radius:8px;background:#f1f5f9" alt="">
                @else
                    <div style="width:70px;height:70px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:1.5rem"><i class="fas fa-box"></i></div>
                @endif
                <div class="flex-fill">
                    <div class="fw-semibold" style="font-size:var(--text-sm)">{{ $item['name'] }}</div>
                    <div class="shop-badge mt-1">{{ $item['tenant_name'] }}</div>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <form action="{{ route('marketplace.cart.update') }}" method="POST" class="d-flex align-items-center gap-1">
                            @csrf
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="listing_id" value="{{ $item['listing_id'] }}">
                            <input type="number" name="qty" value="{{ $item['qty'] }}" min="1" max="999"
                                   class="form-control form-control-sm" style="width:65px" onchange="this.form.submit()">
                        </form>
                        <form action="{{ route('marketplace.cart.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="listing_id" value="{{ $item['listing_id'] }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold" style="color: var(--mp-link)">{{ number_format($item['price'] * $item['qty'],2) }}</div>
                    <div style="font-size:var(--text-xs);color:#94a3b8">{{ number_format($item['price'],2) }} each</div>
                </div>
            </div>
            @endforeach

            <form action="{{ route('marketplace.cart.update') }}" method="POST" class="mt-2">
                @csrf
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-trash me-1"></i>Clear cart
                </button>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="p-4 bg-white rounded-3" style="border:1px solid var(--mp-border);position:sticky;top:80px">
                <h6 class="fw-bold mb-3">Order Summary</h6>
                @php $total = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']); @endphp
                <div class="d-flex justify-content-between small mb-2">
                    <span>Subtotal ({{ count($cart) }} items)</span>
                    <span>{{ number_format($total,2) }}</span>
                </div>
                <div class="d-flex justify-content-between small mb-2 text-muted">
                    <span>Delivery fee</span>
                    <span>Calculated at checkout</span>
                </div>
                <div class="d-flex justify-content-between small mb-2 text-muted">
                    <span>Platform fee</span>
                    <span>Calculated at checkout</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold mb-3">
                    <span>Estimated</span>
                    <span style="color: var(--mp-link)">{{ number_format($total,2) }}+</span>
                </div>
                <a href="{{ route('marketplace.checkout') }}" class="btn-mp d-block text-center" style="padding:.65rem">
                    Proceed to Checkout <i class="fas fa-arrow-right ms-1"></i>
                </a>
                <a href="{{ route('marketplace.index') }}" class="btn-mp-outline d-block text-center mt-2" style="padding:.55rem">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
