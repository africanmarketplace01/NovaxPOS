<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
{{-- Shop name, logo, categories, cart count, customer and payment methods
     come from App\View\Composers\MarketplaceComposer. --}}
<title>@yield('title', 'Shop online') | {{ $mkName }}</title>
<meta name="description" content="@yield('meta-desc', 'Shop electronics, fashion, beauty, groceries and more from verified local stores on '.$mkName.'. Pay on delivery or with mobile money.')">
@if($siteLogoUrl)
    <link rel="icon" type="image/png" href="{{ $siteLogoUrl }}">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $mkTheme['font']) }}:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="{{ asset('fontawesome/css/all.min.css') }}" rel="stylesheet">
<link href="{{ asset('landlord/css/market.css') }}?v={{ @filemtime(public_path('landlord/css/market.css')) }}" rel="stylesheet">
{{-- Theme colours and font from the admin settings. --}}
<style>
  :root {
    --mp-primary: {{ $mkTheme['cta'] }};
    --mp-on-primary: {{ $mkTheme['cta_ink'] }};
    --mp-accent: {{ $mkTheme['cta_on_dark'] }};
    --mp-link: {{ $mkTheme['link'] }};
    --mp-dark: {{ $mkTheme['dark'] }};
    --mp-font: "{{ $mkTheme['font'] }}";
  }
</style>
@yield('extra-css')
</head>
<body>

{{-- Utility strip --}}
<div class="mk-strip">
  <div class="container">
    <div class="mk-strip-links">
      <a href="{{ route('sell') }}" class="mk-strip-sell"><i class="fas fa-store"></i> Sell on {{ $mkNameMain }}</a>
      <a href="{{ route('delivery.register') }}" class="d-none d-md-inline-flex"><i class="fas fa-motorcycle"></i> Deliver with us</a>
    </div>
    <div class="mk-strip-links mk-strip-right">
      <a href="{{ $mpCustomer ? route('customer.orders') : route('customer.login') }}"><i class="fas fa-box"></i> Track my order</a>
      @if($supportPhone)
        <a href="tel:{{ preg_replace('/[^+0-9]/', '', $supportPhone) }}"><i class="fas fa-phone"></i> {{ $supportPhone }}</a>
      @elseif($supportEmail)
        <a href="mailto:{{ $supportEmail }}"><i class="fas fa-headset"></i> Help</a>
      @endif
    </div>
  </div>
</div>

{{-- Header --}}
<header class="mk-header">
  <div class="container">
    <div class="mk-header-row">
      <button class="mk-burger" type="button" onclick="toggleMobileNav()" aria-label="Open menu"><i class="fas fa-bars"></i></button>

      <a href="{{ route('marketplace.index') }}" class="mk-brand" aria-label="{{ $mkName }} home">
        @if($siteLogoUrl)<img src="{{ $siteLogoUrl }}" alt="">@endif
        <span class="mk-brand-name">{{ $mkNameMain }}@if($mkNameSub)<small>&amp; {{ $mkNameSub }}</small>@endif</span>
      </a>

      <form action="{{ route('marketplace.index') }}" method="GET" class="mk-search" role="search">
        <label for="mkSearchCat" class="visually-hidden">Category</label>
        <select name="category" id="mkSearchCat">
          <option value="">All categories</option>
          @foreach($mkCategories as $c)
            <option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>
          @endforeach
        </select>
        <label for="mkSearch" class="visually-hidden">Search</label>
        <input type="search" id="mkSearch" name="q" placeholder="Search products, brands and stores" value="{{ request('q') }}" autocomplete="off">
        <button type="submit"><i class="fas fa-search"></i><span class="d-none d-lg-inline">Search</span></button>
      </form>

      <div class="mk-actions ms-auto">
        <div class="mk-account position-relative" id="mkAccount">
          <button type="button" class="mk-action" onclick="document.getElementById('mkAccount').classList.toggle('open')" aria-haspopup="true">
            <i class="far fa-user"></i>
            <span class="mk-action-text">
              <small>{{ $mpCustomer ? 'Hello, ' . Str::limit(explode(' ', $mpCustomer->name)[0], 12) : 'Hello, sign in' }}</small>
              <strong>Account <i class="fas fa-chevron-down" style="font-size:.6rem"></i></strong>
            </span>
          </button>
          <div class="mk-account-menu">
            @if($mpCustomer)
              <a href="{{ route('customer.dashboard') }}"><i class="far fa-user"></i> My account</a>
              <a href="{{ route('customer.orders') }}"><i class="fas fa-box"></i> Orders</a>
              <a href="{{ route('customer.requests.index') }}"><i class="fas fa-bullhorn"></i> Product requests</a>
              <hr>
              <form action="{{ route('customer.logout') }}" method="POST" class="m-0">@csrf
                <button type="submit"><i class="fas fa-sign-out-alt"></i> Sign out</button>
              </form>
            @else
              <a href="{{ route('customer.login') }}" class="mk-signin">Sign in</a>
              <a href="{{ route('customer.register') }}"><i class="fas fa-user-plus"></i> Create an account</a>
              <hr>
              <a href="{{ route('customer.login') }}"><i class="fas fa-box"></i> Orders</a>
            @endif
          </div>
        </div>
        <a href="{{ route('marketplace.cart') }}" class="mk-action" aria-label="Cart">
          <i class="fas fa-shopping-cart"></i>
          <span class="mk-cart-count" data-cart-count style="{{ $mpCartCount > 0 ? '' : 'display:none' }}">{{ $mpCartCount }}</span>
          <span class="mk-action-text"><small>&nbsp;</small><strong>Cart</strong></span>
        </a>
      </div>
    </div>

    {{-- Mobile search --}}
    <form action="{{ route('marketplace.index') }}" method="GET" class="mk-mobile-search" role="search">
      <div class="mk-search">
        <input type="search" name="q" placeholder="Search products and stores" value="{{ request('q') }}" aria-label="Search">
        <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
      </div>
    </form>
  </div>

  {{-- Category bar --}}
  <nav class="mk-catbar" aria-label="Categories">
    <div class="container">
      <a href="{{ route('marketplace.index', ['sort' => 'discount']) }}" class="mk-deals {{ request('sort') === 'discount' ? 'active' : '' }}"><i class="fas fa-bolt"></i> Today's deals</a>
      <a href="{{ route('marketplace.index', ['sort' => 'newest', 'all' => 1]) }}" class="{{ request('all') ? 'active' : '' }}">New arrivals</a>
      @foreach($mkCategories as $c)
        <a href="{{ route('marketplace.index', ['category' => $c]) }}" class="{{ request('category') === $c ? 'active' : '' }}">{{ $c }}</a>
      @endforeach
      <a href="{{ route('sell') }}" class="mk-catbar-sell">Sell for free</a>
    </div>
  </nav>
</header>

{{-- Mobile drawer --}}
<div class="mp-mobile-nav" id="mpMobileNav">
  <div class="mp-mobile-overlay" onclick="toggleMobileNav()"></div>
  <div class="mp-mobile-drawer" role="dialog" aria-label="Menu">
    <div class="mp-mobile-drawer-head">
      <span class="brand">{{ $mpCustomer ? 'Hello, ' . explode(' ', $mpCustomer->name)[0] : $mkNameMain }}</span>
      <button class="mp-mobile-drawer-close" onclick="toggleMobileNav()" aria-label="Close menu"><i class="fas fa-times"></i></button>
    </div>
    <div class="mp-mobile-drawer-body">
      <a href="{{ route('marketplace.index') }}" class="mp-mob-link"><i class="fas fa-home"></i> Home</a>
      <a href="{{ route('marketplace.index', ['sort' => 'discount']) }}" class="mp-mob-link"><i class="fas fa-bolt"></i> Today's deals</a>
      <a href="{{ route('marketplace.cart') }}" class="mp-mob-link"><i class="fas fa-shopping-cart"></i> Cart <span class="ms-auto badge" style="background:var(--mp-primary)" data-cart-count @if(!$mpCartCount) hidden @endif>{{ $mpCartCount }}</span></a>
      @if(count($mkCategories))
        <div class="mp-mob-title">Categories</div>
        @foreach($mkCategories as $c)
          <a href="{{ route('marketplace.index', ['category' => $c]) }}" class="mp-mob-link"><i class="fas fa-angle-right"></i> {{ $c }}</a>
        @endforeach
      @endif
      <div class="mp-mob-title">My account</div>
      @if($mpCustomer)
        <a href="{{ route('customer.dashboard') }}" class="mp-mob-link"><i class="far fa-user"></i> My account</a>
        <a href="{{ route('customer.orders') }}" class="mp-mob-link"><i class="fas fa-box"></i> Orders</a>
        <a href="{{ route('customer.requests.index') }}" class="mp-mob-link"><i class="fas fa-bullhorn"></i> Product requests</a>
        <form action="{{ route('customer.logout') }}" method="POST" class="m-0">@csrf
          <button class="mp-mob-link"><i class="fas fa-sign-out-alt"></i> Sign out</button>
        </form>
      @else
        <a href="{{ route('customer.login') }}" class="mp-mob-link"><i class="far fa-user"></i> Sign in</a>
        <a href="{{ route('customer.register') }}" class="mp-mob-link"><i class="fas fa-user-plus"></i> Create an account</a>
      @endif
      <hr class="mp-mob-divider">
      <a href="{{ route('sell') }}" class="mp-mob-link"><i class="fas fa-store"></i> Sell on {{ $mkNameMain }}</a>
      <a href="{{ route('delivery.register') }}" class="mp-mob-link"><i class="fas fa-motorcycle"></i> Deliver with us</a>
    </div>
  </div>
</div>

{{-- Flash messages --}}
@if(session('success') || session('error'))
  <div class="container">
    @if(session('success'))<div class="mp-flash mp-flash-success" role="status"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
    @if(session('error'))<div class="mp-flash mp-flash-error" role="alert"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif
  </div>
@endif

<main>
@yield('content')
</main>

@yield('how-it-works')

{{-- Footer --}}
<footer class="mk-footer">
  <div class="mk-footer-sell">
    <div class="container">
      <div><strong>Have something to sell?</strong> Open a free store and reach shoppers across {{ $mkNameMain }}. Pay only when you sell.</div>
      <a href="{{ route('sell') }}">Start selling</a>
    </div>
  </div>
  <div class="container mk-footer-main">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="mk-footer-brand">{{ $mkName }}</div>
        <p style="max-width:330px">Electronics, fashion, beauty, groceries and more from verified local stores, delivered to your door.</p>
        <h3 class="mt-3">Pay with</h3>
        <div class="mk-pay">@foreach(array_keys($mkPay) as $method)<span>{{ $method }}</span>@endforeach</div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <h3>Shop</h3>
        <ul>
          <li><a href="{{ route('marketplace.index', ['sort' => 'discount']) }}">Today's deals</a></li>
          <li><a href="{{ route('marketplace.index', ['sort' => 'newest', 'all' => 1]) }}">New arrivals</a></li>
          @foreach(array_slice($mkCategories, 0, 4) as $c)
            <li><a href="{{ route('marketplace.index', ['category' => $c]) }}">{{ $c }}</a></li>
          @endforeach
        </ul>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <h3>Your account</h3>
        <ul>
          @if($mpCustomer)
            <li><a href="{{ route('customer.dashboard') }}">My account</a></li>
          @else
            <li><a href="{{ route('customer.login') }}">Sign in</a></li>
            <li><a href="{{ route('customer.register') }}">Create an account</a></li>
          @endif
          <li><a href="{{ $mpCustomer ? route('customer.orders') : route('customer.login') }}">Track my order</a></li>
          <li><a href="{{ route('marketplace.cart') }}">Cart</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <h3>Sell &amp; deliver</h3>
        <ul>
          <li><a href="{{ route('sell') }}">Sell for free</a></li>
          <li><a href="{{ route('pricing') }}">POS plans for stores</a></li>
          <li><a href="{{ route('delivery.register') }}">Become a delivery partner</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <h3>Help</h3>
        <ul>
          @if($supportPhone)<li><a href="tel:{{ preg_replace('/[^+0-9]/', '', $supportPhone) }}">{{ $supportPhone }}</a></li>@endif
          @if($supportEmail)<li><a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></li>@endif
          <li><a href="{{ $mpCustomer ? route('customer.orders') : route('customer.login') }}">Where is my order?</a></li>
        </ul>
      </div>
    </div>
    <div class="mk-footer-bottom">
      <span>&copy; {{ date('Y') }} {{ $mkName }}. All rights reserved.</span>
      <a href="{{ route('landing') }}">Powered by {{ $th['site_name'] ?? 'NovaxPOS' }}</a>
    </div>
  </div>
</footer>

{{-- Mobile bottom navigation --}}
<nav class="mp-bottom-nav" aria-label="Quick links">
  <div class="mp-bottom-nav-inner">
    <a href="{{ route('marketplace.index') }}" class="mp-bnav-item {{ request()->routeIs('marketplace.index') && !request()->hasAny(['q','category','sort']) ? 'active' : '' }}"><i class="fas fa-home"></i><span>Home</span></a>
    <button type="button" class="mp-bnav-item" onclick="toggleMobileNav()"><i class="fas fa-th-large"></i><span>Categories</span></button>
    <a href="{{ route('marketplace.index', ['sort' => 'discount']) }}" class="mp-bnav-item {{ request('sort') === 'discount' ? 'active' : '' }}"><i class="fas fa-bolt"></i><span>Deals</span></a>
    <a href="{{ route('marketplace.cart') }}" class="mp-bnav-item {{ request()->routeIs('marketplace.cart') ? 'active' : '' }}">
      <i class="fas fa-shopping-cart"></i><span class="mp-bnav-cnt" data-cart-count style="{{ $mpCartCount > 0 ? '' : 'display:none' }}">{{ $mpCartCount }}</span><span>Cart</span>
    </a>
    <a href="{{ $mpCustomer ? route('customer.dashboard') : route('customer.login') }}" class="mp-bnav-item {{ request()->routeIs('customer.*') ? 'active' : '' }}"><i class="far fa-user"></i><span>Account</span></a>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleMobileNav() {
    var nav = document.getElementById('mpMobileNav');
    nav.classList.toggle('open');
    document.body.style.overflow = nav.classList.contains('open') ? 'hidden' : '';
}
// Close the account menu when clicking elsewhere.
document.addEventListener('click', function (e) {
    var acc = document.getElementById('mkAccount');
    if (acc && !acc.contains(e.target)) acc.classList.remove('open');
});
// Horizontal product shelves: arrow buttons scroll one "page".
document.querySelectorAll('[data-scroller]').forEach(function (wrap) {
    var track = wrap.querySelector('.mk-scroller-track');
    wrap.querySelectorAll('[data-scroll]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            track.scrollBy({ left: (btn.dataset.scroll === 'next' ? 1 : -1) * track.clientWidth * 0.9, behavior: 'smooth' });
        });
    });
});
// ── Live cart count ─────────────────────────────────────────────────────
function setCartCount(count) {
    document.querySelectorAll('[data-cart-count]').forEach(function (el) {
        el.textContent = count;
        el.style.display = count > 0 ? '' : 'none';
        el.hidden = !(count > 0);
    });
}
// ── AJAX add-to-cart (updates the badge without a page reload) ──────────
document.querySelectorAll('form[data-atc-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('[data-atc]');
        if (btn && btn.disabled) return;
        var orig = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin fa-xs"></i> Adding…'; }
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: new FormData(form)
        })
        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
        .then(function (data) {
            if (typeof data.count !== 'undefined') setCartCount(data.count);
            if (btn) {
                btn.innerHTML = '<i class="fas fa-check fa-xs"></i> Added';
                btn.style.background = 'var(--mp-ok)';
                setTimeout(function () { btn.disabled = false; btn.innerHTML = orig; btn.style.background = ''; }, 1400);
            }
        })
        .catch(function () { form.submit(); }); // fall back to a normal post
    });
});
</script>
@stack('scripts')
</body>
</html>
