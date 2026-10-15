@extends('store.layout')
@section('title', $store->store_name ?? 'Store')

@push('styles')
<style>
/* ── Hero ─────────────────────────────────────────────────── */
.store-hero { background: linear-gradient(135deg, var(--store-dark) 0%, #162950 60%, var(--store-primary) 100%); padding: 4rem 0 3.5rem; color: #fff; position: relative; overflow: hidden; }
.store-hero::before { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E"); }
.store-hero-inner { position: relative; }
.store-hero-logo { width: 80px; height: 80px; border-radius: 18px; border: 2.5px solid rgba(255,255,255,.25); background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem; overflow: hidden; }
.store-hero-logo img { width: 100%; height: 100%; object-fit: contain; background: #fff; padding: 6px; border-radius: 16px; }
.store-hero-logo .initials { font-size: 2.2rem; font-weight: 800; color: #fff; }
.store-hero h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 900; line-height: 1.1; margin-bottom: .7rem; }
.store-hero-sub { font-size: .95rem; color: rgba(255,255,255,.7); margin-bottom: 2rem; max-width: 520px; }
.store-hero-search { display: flex; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,.28); max-width: 520px; margin-bottom: 2rem; }
.store-hero-search input { flex: 1; border: none; padding: .85rem 1.1rem; font-size: .9rem; outline: none; min-width: 0; font-family: inherit; }
.store-hero-search button { background: var(--store-accent); color: #1e293b; border: none; padding: .85rem 1.2rem; font-weight: 700; font-size: .88rem; cursor: pointer; white-space: nowrap; font-family: inherit; transition: background .2s; }
.store-hero-search button:hover { background: #ffca2c; }
.store-hero-actions { display: flex; gap: .75rem; flex-wrap: wrap; }
.store-hero-actions .btn-browse { background: #fff; color: var(--store-primary); border: none; border-radius: 10px; padding: .65rem 1.4rem; font-weight: 700; font-size: .88rem; text-decoration: none; display: flex; align-items: center; gap: .4rem; transition: all .2s; }
.store-hero-actions .btn-browse:hover { background: #f0f7ff; }
.store-hero-actions .btn-cart { background: rgba(255,255,255,.15); color: #fff; border: 1.5px solid rgba(255,255,255,.3); border-radius: 10px; padding: .65rem 1.4rem; font-weight: 600; font-size: .88rem; text-decoration: none; display: flex; align-items: center; gap: .4rem; transition: all .2s; }
.store-hero-actions .btn-cart:hover { background: rgba(255,255,255,.25); }

/* ── Category cards ───────────────────────────────────────── */
.cat-card-store { background: var(--store-light-bg); border: 1.5px solid var(--store-border); border-radius: var(--store-radius); padding: 1.1rem .75rem .9rem; text-align: center; text-decoration: none; color: inherit; transition: all .2s; display: block; }
.cat-card-store:hover { border-color: var(--store-primary); background: #fff; box-shadow: 0 4px 16px rgba(0,102,204,.1); transform: translateY(-2px); }
.cat-icon-store { width: 48px; height: 48px; border-radius: 12px; background: rgba(0,102,204,.08); color: var(--store-primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; margin: 0 auto .65rem; transition: all .2s; }
.cat-card-store:hover .cat-icon-store { background: var(--store-primary); color: #fff; }
.cat-name-store { font-weight: 600; font-size: .82rem; color: #1e293b; }
.cat-count-store { font-size: .7rem; color: #94a3b8; margin-top: .2rem; }

/* ── How to order ─────────────────────────────────────────── */
.hto-section { background: #fff; border-top: 1px solid var(--store-border); padding: 3rem 0; }
.hto-step { text-align: center; }
.hto-num { width: 52px; height: 52px; border-radius: 50%; background: rgba(0,102,204,.09); color: var(--store-primary); font-size: 1.25rem; font-weight: 800; display: flex; align-items: center; justify-content: center; margin: 0 auto .85rem; }
.hto-title { font-weight: 700; font-size: .9rem; color: #0f172a; margin-bottom: .3rem; }
.hto-desc { font-size: .78rem; color: #64748b; line-height: 1.5; }
</style>
@endpush

@section('content')

{{-- Hero --}}
<section class="store-hero">
    <div class="container store-hero-inner">
        <div class="row align-items-center">
            <div class="col-lg-7">
                {{-- Store logo or initials --}}
                @php
                    $storeLogoHero = null;
                    if (!empty($store->store_logo)) {
                        $lp = public_path('tenant/' . session('tenant_id', '') . '/logo/' . $store->store_logo);
                        if (file_exists($lp)) $storeLogoHero = asset('tenant/' . session('tenant_id', '') . '/logo/' . $store->store_logo);
                    }
                @endphp
                <div class="store-hero-logo">
                    @if($storeLogoHero)
                        <img src="{{ $storeLogoHero }}" alt="{{ $store->store_name }}">
                    @else
                        <span class="initials">{{ strtoupper(substr($store->store_name ?? 'S', 0, 1)) }}</span>
                    @endif
                </div>

                <h1>Welcome to<br><span style="color:var(--store-accent)">{{ $store->store_name ?? 'Our Store' }}</span></h1>

                @if($store->store_about)
                <p class="store-hero-sub">{{ Str::limit($store->store_about, 120) }}</p>
                @else
                <p class="store-hero-sub">Discover our full range of quality products, delivered right to your door.</p>
                @endif

                <form action="{{ route('store.products') }}" method="GET" class="store-hero-search">
                    <input type="text" name="q" placeholder="Search products…">
                    <button type="submit"><i class="fas fa-search me-1"></i> Search</button>
                </form>

                <div class="store-hero-actions">
                    <a href="{{ route('store.products') }}" class="btn-browse">
                        <i class="fas fa-th-large"></i> Browse Products
                    </a>
                    <a href="{{ route('store.cart') }}" class="btn-cart">
                        <i class="fas fa-shopping-cart"></i> View Cart
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-end">
                <div style="font-size:9rem;opacity:.08;line-height:1">🛍</div>
            </div>
        </div>
    </div>
</section>

{{-- Flash messages --}}
<div class="container mt-3">
    @if(session('cart_success'))
    <div class="flash-success"><i class="fas fa-check-circle"></i> {{ session('cart_success') }}</div>
    @endif
</div>

{{-- Categories --}}
@if($categories->count())
<section style="padding:3rem 0;background:#fff;border-top:1px solid var(--store-border)">
    <div class="container">
        <div class="store-sec-header">
            <div>
                <div class="store-sec-title">Shop by Category</div>
                <div class="store-sec-sub">Find what you need</div>
            </div>
        </div>
        @php
        $storeIcons = [
            'food' => 'fa-utensils', 'restaurant' => 'fa-utensils', 'drinks' => 'fa-glass-martini-alt',
            'clothing' => 'fa-tshirt', 'fashion' => 'fa-tshirt', 'electronics' => 'fa-microchip',
            'cosmetics' => 'fa-spa', 'beauty' => 'fa-spa', 'health' => 'fa-heartbeat',
            'healthcare' => 'fa-heartbeat', 'grocery' => 'fa-shopping-basket',
            'appliance' => 'fa-plug', 'furniture' => 'fa-couch', 'books' => 'fa-book',
            'sports' => 'fa-running', 'toys' => 'fa-puzzle-piece', 'general' => 'fa-boxes',
        ];
        @endphp
        <div class="row g-3">
            @foreach($categories as $cat)
            @php
                $ck = strtolower($cat->name ?? '');
                $si = 'fa-tag';
                foreach ($storeIcons as $k => $v) { if (str_contains($ck, $k)) { $si = $v; break; } }
            @endphp
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ route('store.products', ['category' => $cat->id]) }}" class="cat-card-store">
                    <div class="cat-icon-store"><i class="fas {{ $si }}"></i></div>
                    <div class="cat-name-store">{{ $cat->name }}</div>
                    <div class="cat-count-store">{{ $cat->product_count ?? '' }} products</div>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Featured Products --}}
@if($featured->count())
<section style="padding:3rem 0">
    <div class="container">
        <div class="store-sec-header">
            <div>
                <div class="store-sec-title"><i class="fas fa-star me-1" style="color:var(--store-accent)"></i> Featured Products</div>
                <div class="store-sec-sub">Hand-picked just for you</div>
            </div>
            <a href="{{ route('store.products') }}" class="store-sec-link btn-store-outline px-3" style="font-size:.8rem;padding:.4rem 1rem">View All →</a>
        </div>
        <div class="row g-3">
            @foreach($featured as $product)
            <div class="col-6 col-md-4 col-lg-3">
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
                            <a href="{{ route('store.product', $product->id) }}" style="color:inherit">{{ $product->name }}</a>
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
    </div>
</section>
@endif

{{-- How to Order --}}
<section class="hto-section">
    <div class="container">
        <div class="text-center mb-4">
            <div class="store-sec-title" style="font-size:1.2rem">How to Order</div>
            <div class="store-sec-sub">Simple steps to get what you want</div>
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="hto-step">
                    <div class="hto-num" style="background:rgba(0,102,204,.1)"><i class="fas fa-search" style="font-size:.9rem"></i></div>
                    <div class="hto-title">Browse</div>
                    <div class="hto-desc">Explore our product catalogue</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="hto-step">
                    <div class="hto-num" style="background:rgba(245,158,11,.1);color:#f59e0b"><i class="fas fa-cart-plus" style="font-size:.9rem"></i></div>
                    <div class="hto-title">Add to Cart</div>
                    <div class="hto-desc">Choose your items and qty</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="hto-step">
                    <div class="hto-num" style="background:rgba(34,197,94,.1);color:#22c55e"><i class="fas fa-credit-card" style="font-size:.9rem"></i></div>
                    <div class="hto-title">Checkout</div>
                    <div class="hto-desc">Pay quickly and securely</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="hto-step">
                    <div class="hto-num" style="background:rgba(239,68,68,.1);color:#ef4444"><i class="fas fa-truck" style="font-size:.9rem"></i></div>
                    <div class="hto-title">Delivered</div>
                    <div class="hto-desc">Get it at your doorstep</div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
