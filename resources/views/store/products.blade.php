@extends('store.layout')
@section('title', 'Products — ' . ($store->store_name ?? 'Store'))

@section('content')
<div class="store-breadcrumb">
    <div class="container">
        <a href="{{ route('store.index') }}">Home</a> / <span>Products</span>
    </div>
</div>

<div class="container" style="padding:2rem 0 4rem">
    <div class="row g-4">

        {{-- Sidebar filters --}}
        <div class="col-lg-3">
            <div style="background:#fff;border-radius:12px;border:1px solid var(--store-border);padding:1.25rem;position:sticky;top:80px">
                <div style="font-weight:700;margin-bottom:1rem;font-size:.9rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b">Filters</div>
                <form method="GET" action="{{ route('store.products') }}" id="filter-form">
                    {{-- Search --}}
                    <div class="mb-3">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:.35rem;display:block">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Product name or code…"
                               class="form-control form-control-sm">
                    </div>
                    {{-- Sort --}}
                    <div class="mb-3">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:.35rem;display:block">Sort by</label>
                        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="name"       {{ request('sort','name') === 'name'       ? 'selected' : '' }}>Name A–Z</option>
                            <option value="price_asc"  {{ request('sort') === 'price_asc'         ? 'selected' : '' }}>Price ↑</option>
                            <option value="price_desc" {{ request('sort') === 'price_desc'        ? 'selected' : '' }}>Price ↓</option>
                            <option value="newest"     {{ request('sort') === 'newest'            ? 'selected' : '' }}>Newest</option>
                        </select>
                    </div>
                    {{-- Categories --}}
                    <div class="mb-3">
                        <label style="font-size:.82rem;font-weight:600;margin-bottom:.35rem;display:block">Category</label>
                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All categories</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }} ({{ $cat->product_count }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-store-primary w-100">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <a href="{{ route('store.products') }}" class="btn btn-sm btn-light w-100 mt-2">Clear</a>
                </form>
            </div>
        </div>

        {{-- Products grid --}}
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div style="font-size:.875rem;color:#64748b">
                    Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} products
                </div>
            </div>

            @if($products->count())
            <div class="row g-3">
                @foreach($products as $product)
                <div class="col-6 col-md-4">
                    <div class="product-card">
                        @if($product->promotion_price)
                            <span class="product-card-badge">Sale</span>
                        @endif
                        <div class="product-card-img-wrap">
                            <a href="{{ route('store.product', $product->id) }}">
                                @if($product->image && file_exists(public_path($product->image)))
                                <img src="{{ asset($product->image) }}" class="product-card-img" alt="{{ $product->name }}">
                                @else
                                <div class="product-card-img-placeholder"><i class="fas fa-box-open"></i></div>
                                @endif
                            </a>
                        </div>
                        <div class="product-card-body">
                            <div class="product-card-name">
                                <a href="{{ route('store.product', $product->id) }}" style="color:inherit;text-decoration:none">{{ $product->name }}</a>
                            </div>
                            @if($product->category)
                            <div class="product-card-cat">{{ $product->category->name }}</div>
                            @endif
                            <div class="product-card-price">
                                @if($product->promotion_price)
                                <span class="product-card-promo">{{ number_format($product->price, 2) }}</span>
                                {{ number_format($product->promotion_price, 2) }}
                                @else
                                {{ number_format($product->price, 2) }}
                                @endif
                            </div>
                        </div>
                        <div class="product-card-actions">
                            <form action="{{ route('store.cart.add') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="btn-add-cart">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>

            @else
            <div style="text-align:center;padding:4rem 2rem;background:#fff;border-radius:12px;border:1px solid var(--store-border)">
                <i class="fas fa-search fa-2x mb-3" style="color:#cbd5e1"></i>
                <h5>No products found</h5>
                <p class="text-muted">Try adjusting your filters or search term.</p>
                <a href="{{ route('store.products') }}" class="btn btn-store-primary">Clear filters</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
