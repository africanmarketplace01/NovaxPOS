<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $store->store_name ?? 'Store') — Online Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('fontawesome/css/all.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --store-primary:   #0066CC;
            --store-accent:    #FFB800;
            --store-dark:      #0a1628;
            --store-light-bg:  #f8fafc;
            --store-border:    #e2e8f0;
            --store-radius:    12px;
            --store-shadow:    0 2px 16px rgba(0,0,0,.07);
            --store-shadow-h:  0 8px 32px rgba(0,0,0,.14);
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: var(--store-light-bg); color: #1e293b; margin: 0; padding-bottom: 56px; }
        a { color: var(--store-primary); text-decoration: none; }
        img { max-width: 100%; display: block; }

        /* ── Announcement bar ────────────────────────────────────── */
        .store-announce { background: var(--store-dark); color: rgba(255,255,255,.75); text-align: center; font-size: .72rem; padding: .35rem 1rem; letter-spacing: .01em; }
        .store-announce span { margin: 0 .85rem; }

        /* ── Navbar ──────────────────────────────────────────────── */
        .store-nav {
            background: #fff;
            border-bottom: 1px solid var(--store-border);
            padding: .7rem 0;
            position: sticky; top: 0; z-index: 1000;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .store-nav-inner { display: flex; align-items: center; gap: .85rem; }
        .nav-brand { display: flex; align-items: center; gap: .45rem; font-size: 1.15rem; font-weight: 800; color: var(--store-dark); text-decoration: none; flex-shrink: 0; }
        .nav-brand:hover { color: var(--store-primary); }
        .nav-brand img { max-height: 32px; width: auto; object-fit: contain; }
        .nav-brand .dot { color: var(--store-primary); }
        .nav-search-wrap { flex: 1; max-width: 400px; position: relative; }
        .nav-search { width: 100%; border: 1.5px solid var(--store-border); border-radius: 10px; padding: .5rem 2.8rem .5rem .9rem; font-size: .85rem; outline: none; background: #f8fafc; font-family: inherit; transition: border-color .2s; }
        .nav-search:focus { border-color: var(--store-primary); background: #fff; box-shadow: 0 0 0 3px rgba(0,102,204,.08); }
        .nav-search-btn { position: absolute; right: 0; top: 0; height: 100%; width: 40px; background: var(--store-primary); border: none; border-radius: 0 9px 9px 0; color: #fff; cursor: pointer; font-size: .85rem; }
        .nav-actions { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }
        .nav-cart-btn { display: flex; align-items: center; gap: .4rem; background: var(--store-primary); color: #fff; border-radius: 10px; padding: .5rem 1rem; font-weight: 700; font-size: .8rem; text-decoration: none; transition: background .2s; white-space: nowrap; }
        .nav-cart-btn:hover { background: #0055aa; color: #fff; }
        .nav-cart-cnt { background: var(--store-accent); color: #1e293b; border-radius: 999px; padding: .1em .4em; font-size: .65rem; font-weight: 800; min-width: 1.3em; text-align: center; }
        .nav-hamburger { display: none; background: none; border: none; font-size: 1.2rem; color: #475569; cursor: pointer; flex-shrink: 0; padding: .3rem; }

        /* ── Breadcrumb ───────────────────────────────────────────── */
        .store-breadcrumb { padding: .5rem 0; background: #fff; border-bottom: 1px solid var(--store-border); font-size:.78rem; color: #94a3b8; }
        .store-breadcrumb a { color: #64748b; }
        .store-breadcrumb a:hover { color: var(--store-primary); }

        /* ── Product cards ────────────────────────────────────────── */
        .product-card {
            background: #fff; border-radius: var(--store-radius); overflow: hidden;
            border: 1px solid var(--store-border); transition: transform .2s, box-shadow .2s;
            height: 100%; display: flex; flex-direction: column; position: relative;
        }
        .product-card:hover { transform: translateY(-3px); box-shadow: var(--store-shadow-h); }
        .product-card-badge { position: absolute; top: .5rem; left: .5rem; z-index: 2; background: #ef4444; color: #fff; font-size: .6rem; font-weight: 700; padding: .15em .5em; border-radius: 4px; text-transform: uppercase; }
        .product-card-badge.new { background: #22c55e; }
        .product-card-img { width: 100%; aspect-ratio: 4/3; object-fit: cover; background: #f1f5f9; transition: transform .3s; display: block; }
        .product-card:hover .product-card-img { transform: scale(1.04); }
        .product-card-img-wrap { overflow: hidden; }
        .product-card-img-placeholder { width: 100%; aspect-ratio: 4/3; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 2.5rem; }
        .product-card-body { padding: .85rem; flex: 1; display: flex; flex-direction: column; }
        .product-card-name { font-weight: 600; font-size: .88rem; color: #1e293b; margin-bottom: .25rem; flex: 1; line-height: 1.35; }
        .product-card-cat { font-size: .7rem; color: #94a3b8; margin-bottom: .4rem; }
        .product-card-price { font-size: 1rem; font-weight: 800; color: var(--store-primary); margin-top: auto; }
        .product-card-promo { font-size: .75rem; text-decoration: line-through; color: #94a3b8; margin-right: .3rem; }
        .product-card-actions { padding: 0 .85rem .85rem; }
        .btn-add-cart {
            background: var(--store-primary); color: #fff; border: none;
            border-radius: 8px; font-size: .82rem; font-weight: 600;
            padding: .5rem 1rem; width: 100%; cursor: pointer;
            transition: background .2s; display: flex; align-items: center; justify-content: center; gap: .4rem; font-family: inherit;
        }
        .btn-add-cart:hover { background: #0055aa; }
        .btn-add-cart:disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }

        /* ── Buttons ──────────────────────────────────────────────── */
        .btn-store-primary { background: var(--store-primary); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: .6rem 1.5rem; transition: background .2s; }
        .btn-store-primary:hover { background: #0055aa; color: #fff; }
        .btn-store-outline { border: 2px solid var(--store-primary); color: var(--store-primary); border-radius: 8px; font-weight: 600; background: transparent; padding: .55rem 1.4rem; transition: all .2s; }
        .btn-store-outline:hover { background: var(--store-primary); color: #fff; }

        /* ── Section header ───────────────────────────────────────── */
        .store-sec-title { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0; }
        .store-sec-sub { font-size: .8rem; color: #94a3b8; margin-top: .2rem; }
        .store-sec-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 1.25rem; }
        .store-sec-link { font-size: .82rem; font-weight: 600; color: var(--store-primary); }

        /* ── Mobile drawer ────────────────────────────────────────── */
        .store-mobile-nav { position: fixed; inset: 0; z-index: 2000; display: none; }
        .store-mobile-nav.open { display: block; }
        .store-mobile-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.5); }
        .store-mobile-drawer { position: absolute; top: 0; left: 0; bottom: 0; width: 260px; background: #fff; overflow-y: auto; }
        .store-drawer-head { padding: 1rem 1.25rem; border-bottom: 1px solid var(--store-border); display: flex; align-items: center; justify-content: space-between; }
        .store-drawer-head .brand { font-size: 1.05rem; font-weight: 800; color: var(--store-dark); }
        .store-drawer-close { background: none; border: none; font-size: 1.2rem; color: #64748b; cursor: pointer; }
        .store-mob-link { display: flex; align-items: center; gap: .6rem; padding: .7rem 1.25rem; font-size: .88rem; color: #334155; text-decoration: none; transition: background .15s; }
        .store-mob-link:hover { background: #f8fafc; color: var(--store-primary); }
        .store-mob-link i { width: 18px; text-align: center; color: #94a3b8; }

        /* ── Flash messages ───────────────────────────────────────── */
        .flash-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .875rem; display: flex; align-items: center; gap: .5rem; }
        .flash-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .875rem; display: flex; align-items: center; gap: .5rem; }

        /* ── Footer ───────────────────────────────────────────────── */
        .store-footer { background: var(--store-dark); color: rgba(255,255,255,.6); padding: 2.5rem 0 1.5rem; margin-top: 3rem; }
        .store-footer a { color: rgba(255,255,255,.5); transition: color .15s; }
        .store-footer a:hover { color: #fff; }
        .footer-brand { font-size: 1.1rem; font-weight: 800; color: #fff; margin-bottom: .5rem; }
        .footer-brand .dot { color: var(--store-accent); }
        .footer-col-title { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.35); margin-bottom: .85rem; }
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: .45rem; }
        .footer-links a { font-size: .82rem; }
        .store-footer-divider { border-color: rgba(255,255,255,.08); margin: 1.5rem 0 1rem; }
        .store-footer-bottom { font-size: .74rem; text-align: center; }

        /* ── Mobile bottom nav ────────────────────────────────────── */
        .store-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid var(--store-border); display: none; z-index: 900; box-shadow: 0 -4px 16px rgba(0,0,0,.08); }
        .store-bnav-inner { display: flex; }
        .store-bnav-item { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: .55rem .25rem .45rem; text-decoration: none; color: #94a3b8; font-size: .6rem; font-weight: 600; gap: .2rem; border-top: 2px solid transparent; transition: all .15s; }
        .store-bnav-item i { font-size: 1.1rem; }
        .store-bnav-item.active, .store-bnav-item:hover { color: var(--store-primary); border-top-color: var(--store-primary); }
        .store-bnav-cnt { position: absolute; top: .3rem; right: 50%; transform: translateX(60%); background: #ef4444; color: #fff; font-size: .55rem; font-weight: 800; border-radius: 999px; min-width: 14px; height: 14px; line-height: 14px; text-align: center; padding: 0 3px; }

        /* ── Responsive ───────────────────────────────────────────── */
        @media (max-width: 768px) {
            body { padding-bottom: 60px; }
            .nav-search-wrap { display: none; }
            .nav-hamburger { display: block; }
            .store-bottom-nav { display: block; }
        }
        @media (max-width: 480px) {
            .nav-brand span { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $storeCartCount = count(session('store_cart', []));
    $storeLogo = null;
    if (!empty($store->store_logo)) {
        $logoPath = public_path('tenant/' . session('tenant_id', '') . '/logo/' . $store->store_logo);
        if (file_exists($logoPath)) {
            $storeLogo = asset('tenant/' . session('tenant_id', '') . '/logo/' . $store->store_logo);
        }
    }
@endphp

{{-- Announcement bar --}}
<div class="store-announce d-none d-sm-block">
    <span><i class="fas fa-shipping-fast me-1"></i> Fast local delivery</span>
    <span><i class="fas fa-shield-alt me-1"></i> Secure checkout</span>
    @if($store->whatsapp_number)
    <span><i class="fab fa-whatsapp me-1"></i> WhatsApp us</span>
    @endif
</div>

{{-- Navbar --}}
<nav class="store-nav">
    <div class="container">
        <div class="store-nav-inner">
            <button class="nav-hamburger" onclick="toggleStoreNav()" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>

            <a href="{{ route('store.index') }}" class="nav-brand">
                @if($storeLogo)
                    <img src="{{ $storeLogo }}" alt="{{ $store->store_name ?? 'Store' }}">
                @endif
                <span>{{ $store->store_name ?? 'Store' }}<span class="dot">.</span></span>
            </a>

            <form action="{{ route('store.products') }}" method="GET" class="nav-search-wrap">
                <input type="text" name="q" class="nav-search" placeholder="Search products…" value="{{ request('q') }}">
                <button type="submit" class="nav-search-btn"><i class="fas fa-search"></i></button>
            </form>

            <div class="nav-actions">
                <a href="{{ route('store.cart') }}" class="nav-cart-btn">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                    @if($storeCartCount > 0)
                        <span class="nav-cart-cnt">{{ $storeCartCount }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</nav>

{{-- Mobile drawer --}}
<div class="store-mobile-nav" id="storeMobileNav">
    <div class="store-mobile-overlay" onclick="toggleStoreNav()"></div>
    <div class="store-mobile-drawer">
        <div class="store-drawer-head">
            <span class="brand">{{ $store->store_name ?? 'Store' }}<span style="color:var(--store-primary)">.</span></span>
            <button class="store-drawer-close" onclick="toggleStoreNav()"><i class="fas fa-times"></i></button>
        </div>
        <a href="{{ route('store.index') }}" class="store-mob-link"><i class="fas fa-home"></i> Home</a>
        <a href="{{ route('store.products') }}" class="store-mob-link"><i class="fas fa-th-large"></i> All Products</a>
        <a href="{{ route('store.cart') }}" class="store-mob-link"><i class="fas fa-shopping-cart"></i> Cart @if($storeCartCount > 0)<span class="ms-auto badge bg-danger">{{ $storeCartCount }}</span>@endif</a>
        <a href="{{ route('store.checkout') }}" class="store-mob-link"><i class="fas fa-credit-card"></i> Checkout</a>
        @if($store->whatsapp_number)
        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $store->whatsapp_number) }}" target="_blank" class="store-mob-link" style="color:#25D366">
            <i class="fab fa-whatsapp"></i> WhatsApp Us
        </a>
        @endif
    </div>
</div>

{{-- Flash messages --}}
<div class="container mt-3">
    @if(session('cart_success'))
    <div class="flash-success"><i class="fas fa-check-circle"></i> {{ session('cart_success') }}</div>
    @endif
    @if(session('error'))
    <div class="flash-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif
</div>

{{-- Content --}}
<main>
    @yield('content')
</main>

{{-- Footer --}}
<footer class="store-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="footer-brand">{{ $store->store_name ?? 'Store' }}<span class="dot">.</span></div>
                @if($store->store_about)
                <p style="font-size:.82rem;line-height:1.6">{{ Str::limit($store->store_about, 160) }}</p>
                @endif
                @if($store->whatsapp_number)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $store->whatsapp_number) }}" target="_blank"
                   style="display:inline-flex;align-items:center;gap:.4rem;background:#25D366;color:#fff;border-radius:8px;padding:.45rem 1rem;font-size:.8rem;font-weight:600;margin-top:.75rem">
                    <i class="fab fa-whatsapp"></i> WhatsApp Us
                </a>
                @endif
            </div>
            <div class="col-6 col-md-3">
                <div class="footer-col-title">Quick Links</div>
                <ul class="footer-links">
                    <li><a href="{{ route('store.index') }}">Home</a></li>
                    <li><a href="{{ route('store.products') }}">All Products</a></li>
                    <li><a href="{{ route('store.cart') }}">Cart</a></li>
                    <li><a href="{{ route('store.checkout') }}">Checkout</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3">
                <div class="footer-col-title">Support</div>
                <ul class="footer-links">
                    @if($store->whatsapp_number)
                    <li><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $store->whatsapp_number) }}" target="_blank">WhatsApp</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <hr class="store-footer-divider">
        <div class="store-footer-bottom">
            &copy; {{ date('Y') }} {{ $store->store_name ?? 'Store' }}. Powered by NovaxPOS.
        </div>
    </div>
</footer>

{{-- Mobile bottom nav --}}
<nav class="store-bottom-nav">
    <div class="store-bnav-inner">
        <a href="{{ route('store.index') }}" class="store-bnav-item {{ request()->routeIs('store.index') ? 'active' : '' }}">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
        <a href="{{ route('store.products') }}" class="store-bnav-item {{ request()->routeIs('store.products') ? 'active' : '' }}">
            <i class="fas fa-th-large"></i><span>Products</span>
        </a>
        <a href="{{ route('store.cart') }}" class="store-bnav-item {{ request()->routeIs('store.cart') ? 'active' : '' }}" style="position:relative">
            <i class="fas fa-shopping-cart"></i>
            @if($storeCartCount > 0)<span class="store-bnav-cnt">{{ $storeCartCount }}</span>@endif
            <span>Cart</span>
        </a>
        <a href="{{ route('store.checkout') }}" class="store-bnav-item">
            <i class="fas fa-credit-card"></i><span>Order</span>
        </a>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleStoreNav() {
    const nav = document.getElementById('storeMobileNav');
    nav.classList.toggle('open');
    document.body.style.overflow = nav.classList.contains('open') ? 'hidden' : '';
}
</script>
@stack('scripts')
</body>
</html>
