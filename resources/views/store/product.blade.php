@extends('store.layout')
@section('title', $product->name . ' — ' . ($store->store_name ?? 'Store'))

@section('content')
<div class="store-breadcrumb">
    <div class="container">
        <a href="{{ route('store.index') }}">Home</a> /
        <a href="{{ route('store.products') }}">Products</a> /
        <span>{{ Str::limit($product->name, 40) }}</span>
    </div>
</div>

<div class="container" style="padding:2.5rem 0 4rem">

    @if(session('cart_success'))
    <div class="flash-success"><i class="fas fa-check-circle me-2"></i>{{ session('cart_success') }}</div>
    @endif

    <div class="row g-5">
        {{-- Product image --}}
        <div class="col-md-5">
            <div style="background:#fff;border-radius:16px;overflow:hidden;border:1px solid var(--store-border);aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;">
                @if($product->image && file_exists(public_path($product->image)))
                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}"
                     style="width:100%;height:100%;object-fit:cover">
                @else
                <i class="fas fa-box-open fa-4x" style="color:#cbd5e1"></i>
                @endif
            </div>
        </div>

        {{-- Product info --}}
        <div class="col-md-7">
            @if($product->category)
            <div style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--store-primary);font-weight:600;margin-bottom:.5rem">
                {{ $product->category->name }}
            </div>
            @endif
            <h1 style="font-size:1.8rem;font-weight:800;line-height:1.2;margin-bottom:.75rem">{{ $product->name }}</h1>

            @if($product->brand)
            <div style="font-size:.85rem;color:#64748b;margin-bottom:1rem">Brand: <strong>{{ $product->brand->title }}</strong></div>
            @endif

            {{-- Price --}}
            <div style="margin-bottom:1.25rem">
                @if($product->promotion_price)
                <span style="font-size:1rem;text-decoration:line-through;color:#94a3b8;margin-right:.5rem">
                    {{ number_format($product->price, 2) }}
                </span>
                <span style="font-size:2rem;font-weight:800;color:var(--store-primary)">
                    {{ number_format($product->promotion_price, 2) }}
                </span>
                <span style="display:inline-block;background:#dcfce7;color:#166534;font-size:.75rem;font-weight:700;padding:.2em .6em;border-radius:999px;margin-left:.5rem">
                    SALE
                </span>
                @else
                <span style="font-size:2rem;font-weight:800;color:var(--store-primary)">
                    {{ number_format($product->price, 2) }}
                </span>
                @endif
                @if($product->wholesale_price && $product->wholesale_price > 0)
                <div style="font-size:.82rem;color:#64748b;margin-top:.25rem">
                    Wholesale: <strong>{{ number_format($product->wholesale_price, 2) }}</strong>
                </div>
                @endif
            </div>

            {{-- Description --}}
            @if($product->short_description)
            <div style="font-size:.9rem;color:#475569;line-height:1.7;margin-bottom:1.5rem">
                {!! nl2br(e($product->short_description)) !!}
            </div>
            @endif

            {{-- Stock badge --}}
            <div style="margin-bottom:1.5rem">
                @if($product->qty > 0)
                <span style="background:#dcfce7;color:#166534;font-size:.78rem;font-weight:700;padding:.3em .8em;border-radius:999px">
                    <i class="fas fa-check-circle me-1"></i>In Stock ({{ $product->qty }})
                </span>
                @else
                <span style="background:#fee2e2;color:#991b1b;font-size:.78rem;font-weight:700;padding:.3em .8em;border-radius:999px">
                    <i class="fas fa-times-circle me-1"></i> Out of Stock
                </span>
                @endif
            </div>

            {{-- Add to cart form --}}
            @if($store->allow_orders)
            <form action="{{ route('store.cart.add') }}" method="POST" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div style="display:flex;align-items:center;border:1px solid var(--store-border);border-radius:8px;overflow:hidden">
                    <button type="button" onclick="changeQty(-1)"
                            style="width:40px;height:42px;border:none;background:#f8fafc;cursor:pointer;font-size:1.1rem;color:#475569">−</button>
                    <input type="number" name="qty" id="qty-input" value="1" min="1" max="{{ $product->qty ?: 999 }}"
                           style="width:60px;border:none;text-align:center;font-weight:700;font-size:1rem;outline:none">
                    <button type="button" onclick="changeQty(1)"
                            style="width:40px;height:42px;border:none;background:#f8fafc;cursor:pointer;font-size:1.1rem;color:#475569">+</button>
                </div>
                <button type="submit" class="btn btn-store-primary px-4" style="height:42px;font-size:.95rem">
                    <i class="fas fa-cart-plus me-2"></i> Add to Cart
                </button>
            </form>

            @if($store->whatsapp_number)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $store->whatsapp_number) }}?text={{ urlencode('Hi! I\'m interested in: ' . $product->name . ' (' . url()->current() . ')') }}"
               target="_blank" class="btn mt-3"
               style="background:#25D366;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:.9rem;padding:.5rem 1.25rem">
                <i class="fab fa-whatsapp me-2"></i> Order via WhatsApp
            </a>
            @endif
            @endif

            {{-- Product details table --}}
            <div style="margin-top:2rem;background:#f8fafc;border:1px solid var(--store-border);border-radius:10px;overflow:hidden">
                <table class="table table-sm table-borderless mb-0" style="font-size:.85rem">
                    <tbody>
                        @if($product->code)
                        <tr><td style="color:#64748b;width:40%">SKU/Code</td><td><strong>{{ $product->code }}</strong></td></tr>
                        @endif
                        @if($product->brand)
                        <tr><td style="color:#64748b">Brand</td><td>{{ $product->brand->title }}</td></tr>
                        @endif
                        @if($product->category)
                        <tr><td style="color:#64748b">Category</td><td>{{ $product->category->name }}</td></tr>
                        @endif
                        @if($product->warranty)
                        <tr><td style="color:#64748b">Warranty</td><td>{{ $product->warranty }}</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Related products --}}
    @if($related->count())
    <div style="margin-top:4rem">
        <h3 style="font-size:1.3rem;font-weight:700;margin-bottom:1.25rem">Related Products</h3>
        <div class="row g-3">
            @foreach($related as $rel)
            <div class="col-6 col-md-3">
                <div class="product-card">
                    <a href="{{ route('store.product', $rel->id) }}" style="text-decoration:none;display:block">
                        @if($rel->image && file_exists(public_path($rel->image)))
                        <img src="{{ asset($rel->image) }}" class="product-card-img" alt="{{ $rel->name }}">
                        @else
                        <div class="product-card-img-placeholder"><i class="fas fa-box-open"></i></div>
                        @endif
                    </a>
                    <div class="product-card-body">
                        <div class="product-card-name">{{ $rel->name }}</div>
                        <div class="product-card-price">{{ number_format($rel->promotion_price ?: $rel->price, 2) }}</div>
                    </div>
                    <div class="product-card-actions">
                        <form action="{{ route('store.cart.add') }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $rel->id }}">
                            <input type="hidden" name="qty" value="1">
                            <button type="submit" class="btn-add-cart">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function changeQty(delta) {
    var inp = document.getElementById('qty-input');
    var val = parseInt(inp.value) + delta;
    inp.value = Math.max(1, val);
}
</script>
@endpush
