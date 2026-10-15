@extends('landlord.marketplace.layout')

@php
    $browsing = \App\Http\Controllers\landlord\MarketplaceController::isBrowsing(request());
    $q = trim((string) request('q'));
    $pageTitle = match (true) {
        $q !== '' => 'Results for "' . $q . '"',
        request()->filled('category') => request('category'),
        request('sort') === 'discount' => "Today's deals",
        (bool) request('all') => 'New arrivals',
        default => 'All products',
    };
    // Soft backgrounds for hero slides, cycled per slide.
    $slideTones = ['#fff1e3', '#e9f1fb', '#eaf6ee', '#fbeef3'];
    $slides = collect($featured ?? [])->filter(fn ($f) => $f->image_url)->take(4)->values();
@endphp

@section('title', $browsing ? $pageTitle : 'Shop online from local stores')

@section('extra-css')
<style>
/* ── Hero ────────────────────────────────────────────────────────────── */
.mk-hero { display: grid; grid-template-columns: 220px minmax(0, 1fr) 250px; gap: .9rem; margin-top: 1rem; }
@media (max-width: 1199px) { .mk-hero { grid-template-columns: 200px minmax(0, 1fr); } .mk-hero-side { display: none !important; } }
@media (max-width: 991px) { .mk-hero { grid-template-columns: minmax(0, 1fr); } .mk-hero-cats { display: none; } }
.mk-hero-cats { padding: .5rem 0; }
.mk-hero-cats a { display: flex; align-items: center; justify-content: space-between; padding: .5rem 1rem; font-size: var(--text-sm); color: var(--mp-ink); }
.mk-hero-cats a:hover { background: var(--mp-tint); color: var(--mp-link); }
.mk-hero-cats a i { font-size: .7rem; color: #c4c4c8; }
.mk-hero-cats .mk-hero-cats-title { font-weight: 800; font-size: var(--text-xs); text-transform: uppercase; letter-spacing: .05em; color: var(--mp-muted); padding: .35rem 1rem .5rem; }
.mk-carousel { position: relative; border-radius: var(--mp-card-r); overflow: hidden; min-height: 340px; box-shadow: var(--mp-shadow); }
.mk-slide { position: absolute; inset: 0; display: grid; grid-template-columns: 1fr 1fr; align-items: center; opacity: 0; visibility: hidden; transition: opacity .6s, visibility .6s; }
.mk-slide.is-on { opacity: 1; visibility: visible; }
.mk-slide-copy { padding: 2rem 1rem 2rem 2.4rem; }
.mk-slide-store { font-size: var(--text-xs); font-weight: 700; color: var(--mp-link); text-transform: uppercase; letter-spacing: .05em; }
.mk-slide h2 { font-size: clamp(1.5rem, 2.6vw, 2.25rem); font-weight: 800; line-height: 1.1; margin: .5rem 0 .8rem; letter-spacing: -.02em; }
.mk-slide-price { font-size: 1.5rem; font-weight: 800; }
.mk-slide-price s { font-size: var(--text-base); color: var(--mp-muted); font-weight: 500; margin-left: .4rem; }
.mk-slide-off { display: inline-block; background: var(--mp-sale); color: #fff; font-weight: 800; font-size: var(--text-xs); padding: .15rem .5rem; border-radius: 4px; margin-left: .4rem; vertical-align: middle; }
.mk-slide .btn-mp { margin-top: 1.2rem; }
.mk-slide-img { height: 100%; min-height: 340px; }
.mk-slide-img img { width: 100%; height: 100%; object-fit: cover; }
.mk-dots { position: absolute; left: 2.4rem; bottom: 1rem; display: flex; gap: .4rem; z-index: 3; }
.mk-dots button { width: 10px; height: 10px; border-radius: 999px; border: 0; background: rgba(0, 0, 0, .2); padding: 0; transition: width .2s; }
.mk-dots button.is-on { width: 26px; background: var(--mp-primary); }
@media (max-width: 575px) {
    .mk-carousel, .mk-slide-img { min-height: 260px; }
    .mk-slide { grid-template-columns: 1.1fr .9fr; }
    .mk-slide-copy { padding: 1.2rem .5rem 2.2rem 1.1rem; }
    .mk-slide h2 { font-size: var(--text-lg); }
    .mk-slide-price { font-size: var(--text-lg); }
    .mk-slide .btn-mp { padding: .45rem .8rem; font-size: var(--text-xs); }
    .mk-dots { left: 1.1rem; }
}
.mk-hero-side { display: grid; grid-template-rows: 1fr 1fr; gap: .9rem; }
.mk-tile { border-radius: var(--mp-card-r); padding: 1.1rem; display: flex; flex-direction: column; justify-content: space-between; color: var(--mp-ink); box-shadow: var(--mp-shadow); }
.mk-tile:hover { color: var(--mp-ink); box-shadow: var(--mp-shadow-h); }
.mk-tile h3 { font-size: var(--text-lg); font-weight: 800; margin: .3rem 0 .3rem; line-height: 1.2; }
.mk-tile p { font-size: var(--text-sm); color: #52525b; margin: 0; }
.mk-tile span.go { font-weight: 800; font-size: var(--text-sm); color: var(--mp-link); }
.mk-tile--sell { background: #1f2430; color: #fff; }
.mk-tile--sell:hover { color: #fff; }
.mk-tile--sell p { color: rgba(255, 255, 255, .75); }
.mk-tile--sell span.go { color: var(--mp-accent); }
.mk-tile--track { background: #fff; }
.mk-tile i.big { font-size: 1.5rem; color: var(--mp-link); }

/* ── Trust strip ─────────────────────────────────────────────────────── */
.mk-trust { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); }
.mk-trust div { display: flex; align-items: center; gap: .75rem; padding: .9rem 1.1rem; }
.mk-trust div + div { border-left: 1px solid var(--mp-border); }
.mk-trust i { font-size: 1.35rem; color: var(--mp-link); width: 1.6rem; text-align: center; }
.mk-trust strong { display: block; font-size: var(--text-sm); }
.mk-trust small { color: var(--mp-muted); font-size: var(--text-xs); }
@media (max-width: 991px) { .mk-trust { grid-template-columns: repeat(2, minmax(0, 1fr)); } .mk-trust div:nth-child(3) { border-left: 0; } .mk-trust div:nth-child(n+3) { border-top: 1px solid var(--mp-border); } }

/* ── Category tiles ──────────────────────────────────────────────────── */
.mk-cats { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .8rem; }
@media (max-width: 991px) { .mk-cats { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
@media (max-width: 575px) { .mk-cats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
.mk-cat { text-align: center; color: var(--mp-ink); }
.mk-cat-img { aspect-ratio: 1 / 1; border-radius: 50%; overflow: hidden; background: #f4f4f5; border: 3px solid #fff; box-shadow: 0 0 0 1px var(--mp-border); transition: box-shadow .18s; }
.mk-cat:hover .mk-cat-img { box-shadow: 0 0 0 2px var(--mp-primary); }
.mk-cat-img img { width: 100%; height: 100%; object-fit: cover; }
.mk-cat-img span { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 800; color: var(--mp-link); }
.mk-cat strong { display: block; font-size: var(--text-sm); margin-top: .5rem; }
.mk-cat small { color: var(--mp-muted); font-size: var(--text-xs); }
.mk-cat:hover { color: var(--mp-link); }

/* Stores */
.mk-stores { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; }
@media (max-width: 991px) { .mk-stores { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 575px) { .mk-stores { grid-template-columns: minmax(0, 1fr); } }
</style>
@endsection

@section('content')
<div class="container">

@if(!$browsing)
  {{-- ═════════════════════════════ Shop front ═════════════════════════════ --}}
  <section class="mk-hero" aria-label="Featured">
    <nav class="mk-panel mk-hero-cats" aria-label="Shop by category">
      <div class="mk-hero-cats-title">Categories</div>
      @forelse($categories->take(10) as $cat)
        <a href="{{ route('marketplace.index', ['category' => $cat]) }}">{{ $cat }} <i class="fas fa-chevron-right"></i></a>
      @empty
        <a href="{{ route('marketplace.index', ['all' => 1]) }}">All products <i class="fas fa-chevron-right"></i></a>
      @endforelse
      <a href="{{ route('marketplace.index', ['sort' => 'discount']) }}" style="color:var(--mp-sale);font-weight:700">Today's deals <i class="fas fa-chevron-right"></i></a>
    </nav>

    <div class="mk-carousel" id="mkCarousel" aria-roledescription="carousel">
      @forelse($slides as $i => $s)
        @php $sale = $s->promo_price && $s->promo_price < $s->price; @endphp
        <div class="mk-slide @if($i === 0) is-on @endif" style="background:{{ $slideTones[$i % count($slideTones)] }}" aria-hidden="{{ $i === 0 ? 'false' : 'true' }}">
          <div class="mk-slide-copy">
            <div class="mk-slide-store">{{ $s->tenant_store_name ?: $s->tenant_name }}</div>
            <h2>{{ Str::limit($s->name, 48) }}</h2>
            <div class="mk-slide-price">
              {{ $s->currency }} {{ number_format($sale ? $s->promo_price : $s->price, 2) }}
              @if($sale)<s>{{ number_format($s->price, 2) }}</s><span class="mk-slide-off">-{{ round((1 - $s->promo_price / $s->price) * 100) }}%</span>@endif
            </div>
            <a href="{{ route('marketplace.product', $s->id) }}" class="btn-mp">Shop now</a>
          </div>
          <div class="mk-slide-img"><img src="{{ $s->image_url }}" alt="{{ $s->name }}" @if($i > 0) loading="lazy" @endif></div>
        </div>
      @empty
        <div class="mk-slide is-on" style="background:#fff1e3;grid-template-columns:1fr">
          <div class="mk-slide-copy">
            <div class="mk-slide-store">Opening soon</div>
            <h2>Local stores, one place to shop.</h2>
            <p style="color:#52525b;max-width:420px">Stores are setting up their shelves. Run a business? List your products for free today.</p>
            <a href="{{ route('sell') }}" class="btn-mp">Start selling</a>
          </div>
        </div>
      @endforelse
      @if($slides->count() > 1)
        <div class="mk-dots" role="tablist" aria-label="Choose slide">
          @foreach($slides as $i => $s)
            <button type="button" class="@if($i === 0) is-on @endif" data-slide-to="{{ $i }}" aria-label="Slide {{ $i + 1 }}"></button>
          @endforeach
        </div>
      @endif
    </div>

    <div class="mk-hero-side">
      <a href="{{ route('sell') }}" class="mk-tile mk-tile--sell">
        <div><i class="fas fa-store big" style="color:var(--mp-accent)"></i><h3>Sell on {{ $mkNameMain }}</h3><p>Open a free store. Pay only when you make a sale.</p></div>
        <span class="go">Start selling <i class="fas fa-arrow-right"></i></span>
      </a>
      <a href="{{ $mpCustomer ? route('customer.orders') : route('customer.login') }}" class="mk-tile mk-tile--track">
        <div><i class="fas fa-truck big"></i><h3>Track your order</h3><p>See where your delivery is, step by step.</p></div>
        <span class="go">Track now <i class="fas fa-arrow-right"></i></span>
      </a>
    </div>
  </section>

  {{-- Only true promises: these all exist on the platform today. --}}
  <section class="mk-panel mk-section mk-trust" aria-label="Why shop here">
    <div><i class="fas fa-hand-holding-usd"></i><span><strong>Pay on delivery</strong><small>Pay cash when your order arrives</small></span></div>
    <div><i class="fas fa-mobile-alt"></i><span><strong>{{ isset($mkPay['MTN MoMo']) || isset($mkPay['Orange Money']) ? 'Mobile money accepted' : 'Secure checkout' }}</strong><small>{{ implode(', ', array_slice(array_keys($mkPay), 1, 3)) ?: 'Safe and simple payment' }}</small></span></div>
    <div><i class="fas fa-user-check"></i><span><strong>Verified local stores</strong><small>Every seller is reviewed</small></span></div>
    <div><i class="fas fa-motorcycle"></i><span><strong>Choose your courier</strong><small>Delivery partners at checkout</small></span></div>
  </section>

  @if($deals->count())
    <section class="mk-shelf mk-section" aria-labelledby="dealsTitle">
      <div class="mk-shelf-head mk-shelf-head--deal">
        <h2 id="dealsTitle"><i class="fas fa-bolt"></i> Top deals</h2>
        <a href="{{ route('marketplace.index', ['sort' => 'discount']) }}">See all <i class="fas fa-angle-right"></i></a>
      </div>
      <div class="mk-shelf-body">
        <div class="mk-scroller" data-scroller>
          <button type="button" class="mk-scroll-btn prev" data-scroll="prev" aria-label="Scroll left"><i class="fas fa-chevron-left"></i></button>
          <div class="mk-scroller-track">
            @foreach($deals as $product)@include('landlord.marketplace._product-card')@endforeach
          </div>
          <button type="button" class="mk-scroll-btn next" data-scroll="next" aria-label="Scroll right"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </section>
  @endif

  @if($categoryTiles->count())
    <section class="mk-shelf mk-section" aria-labelledby="catsTitle">
      <div class="mk-shelf-head"><h2 id="catsTitle">Shop by category</h2></div>
      <div class="mk-shelf-body">
        <div class="mk-cats">
          @foreach($categoryTiles as $tile)
            <a href="{{ route('marketplace.index', ['category' => $tile->name]) }}" class="mk-cat">
              <div class="mk-cat-img">
                @if($tile->image_url)<img src="{{ $tile->image_url }}" alt="" loading="lazy">@else<span>{{ strtoupper(substr($tile->name, 0, 1)) }}</span>@endif
              </div>
              <strong>{{ $tile->name }}</strong>
              <small>{{ number_format($tile->total) }} {{ Str::plural('item', $tile->total) }}</small>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  @if($newArrivals->count())
    <section class="mk-shelf mk-section" aria-labelledby="newTitle">
      <div class="mk-shelf-head">
        <h2 id="newTitle">New arrivals</h2>
        <a href="{{ route('marketplace.index', ['sort' => 'newest', 'all' => 1]) }}">See all <i class="fas fa-angle-right"></i></a>
      </div>
      <div class="mk-shelf-body">
        <div class="mk-scroller" data-scroller>
          <button type="button" class="mk-scroll-btn prev" data-scroll="prev" aria-label="Scroll left"><i class="fas fa-chevron-left"></i></button>
          <div class="mk-scroller-track">
            @foreach($newArrivals as $product)@include('landlord.marketplace._product-card')@endforeach
          </div>
          <button type="button" class="mk-scroll-btn next" data-scroll="next" aria-label="Scroll right"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </section>
  @endif

  @foreach($categoryRows as $row)
    <section class="mk-shelf mk-section" aria-label="{{ $row->name }}">
      <div class="mk-shelf-head">
        <h2>Best of {{ $row->name }}</h2>
        <a href="{{ route('marketplace.index', ['category' => $row->name]) }}">See all <i class="fas fa-angle-right"></i></a>
      </div>
      <div class="mk-shelf-body">
        <div class="mk-scroller" data-scroller>
          <button type="button" class="mk-scroll-btn prev" data-scroll="prev" aria-label="Scroll left"><i class="fas fa-chevron-left"></i></button>
          <div class="mk-scroller-track">
            @foreach($row->products as $product)@include('landlord.marketplace._product-card')@endforeach
          </div>
          <button type="button" class="mk-scroll-btn next" data-scroll="next" aria-label="Scroll right"><i class="fas fa-chevron-right"></i></button>
        </div>
      </div>
    </section>
  @endforeach

  @if($shops->count())
    <section class="mk-shelf mk-section" aria-labelledby="storesTitle">
      <div class="mk-shelf-head"><h2 id="storesTitle">Shop by store</h2></div>
      <div class="mk-shelf-body">
        <div class="mk-stores">
          @foreach($shops->take(9) as $shop)
            @php $shopName = $shop->tenant_store_name ?: $shop->tenant_name; @endphp
            <a href="{{ route('marketplace.shop', $shop->tenant_id) }}" class="vcard">
              @if(!empty($shop->logo_url))
                <img src="{{ $shop->logo_url }}" alt="" class="vcard-logo">
              @else
                <span class="vcard-avatar">{{ strtoupper(substr($shopName, 0, 1)) }}</span>
              @endif
              <span class="vcard-body">
                <span class="vcard-name d-block">{{ $shopName }}</span>
                <span class="vcard-meta"><span class="vcard-badge"><i class="fas fa-check-circle"></i> Verified store</span><span>{{ number_format($shop->product_count) }} {{ Str::plural('item', $shop->product_count) }}</span></span>
              </span>
              <span class="vcard-visit">Visit <i class="fas fa-angle-right"></i></span>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  @if($products->count())
    <section class="mk-section" aria-labelledby="allTitle">
      <div class="mk-results-bar">
        <h1 id="allTitle" style="font-size:var(--text-lg)">Recommended for you</h1>
        <a href="{{ route('marketplace.index', ['all' => 1]) }}" class="sec-link">Browse everything <i class="fas fa-angle-right"></i></a>
      </div>
      <div class="mk-grid">
        @foreach($products->take(15) as $product)@include('landlord.marketplace._product-card')@endforeach
      </div>
    </section>
  @endif

@else
  {{-- ═════════════════════════ Search & category results ═════════════════════════ --}}
  <div class="mp-bc">
    <a href="{{ route('marketplace.index') }}">Home</a><span class="sep">/</span>{{ $pageTitle }}
  </div>

  <div class="row g-3 mt-0">
    <aside class="col-lg-3 col-xl-2 d-none d-lg-block">
      <div class="filter-box">
        <div class="filter-title">Category</div>
        <a href="{{ route('marketplace.index', array_merge(request()->except('category', 'page'), ['all' => 1])) }}" class="filter-link {{ !request('category') ? 'active' : '' }}">All categories</a>
        @foreach($categories as $cat)
          <a href="{{ route('marketplace.index', array_merge(request()->except('page', 'all'), ['category' => $cat])) }}" class="filter-link {{ request('category') === $cat ? 'active' : '' }}">{{ $cat }}</a>
        @endforeach
      </div>
      <form method="GET" action="{{ route('marketplace.index') }}" class="filter-box">
        @foreach(request()->except('price_min', 'price_max', 'page') as $k => $v)
          @if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <div class="filter-title">Price (USD)</div>
        <div class="mk-price-filter">
          <input type="number" name="price_min" min="0" step="1" placeholder="Min" value="{{ request('price_min') }}" aria-label="Minimum price">
          <input type="number" name="price_max" min="0" step="1" placeholder="Max" value="{{ request('price_max') }}" aria-label="Maximum price">
        </div>
        <button type="submit" class="btn-mp w-100 mt-2" style="padding:.4rem;font-size:var(--text-sm)">Apply</button>
      </form>
      <div class="filter-box">
        <div class="filter-title">Availability</div>
        <label class="d-flex align-items-center gap-2" style="font-size:var(--text-sm);cursor:pointer">
          <input type="checkbox" class="form-check-input m-0" @checked(request('in_stock'))
                 onchange="location.href='{{ route('marketplace.index', array_merge(request()->except('in_stock', 'page'), ['in_stock' => 1])) }}'.replace(/([?&])in_stock=1/, this.checked ? '$1in_stock=1' : '$1')">
          In stock only
        </label>
      </div>
    </aside>

    <div class="col-lg-9 col-xl-10">
      <div class="mk-results-bar">
        <div>
          <h1>{{ $pageTitle }}</h1>
          <span class="count">{{ number_format($products->total()) }} {{ Str::plural('result', $products->total()) }}</span>
        </div>
        <label class="d-flex align-items-center gap-2" style="font-size:var(--text-sm)">
          <span class="text-nowrap">Sort by</span>
          <select class="form-select form-select-sm" style="width:auto" onchange="location.href=this.value">
            @foreach(['newest' => 'Newest', 'discount' => 'Biggest discount', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name (A–Z)'] as $key => $label)
              <option value="{{ route('marketplace.index', array_merge(request()->except('sort', 'page'), ['sort' => $key])) }}" @selected(request('sort', 'newest') === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
      </div>

      <div class="cat-pills mb-2 d-lg-none">
        <a href="{{ route('marketplace.index', ['all' => 1]) }}" class="cat-pill {{ !request('category') ? 'active' : '' }}">All</a>
        @foreach($categories as $cat)
          <a href="{{ route('marketplace.index', ['category' => $cat]) }}" class="cat-pill {{ request('category') === $cat ? 'active' : '' }}">{{ $cat }}</a>
        @endforeach
      </div>

      @if($products->isEmpty())
        <div class="empty-state">
          <i class="fas fa-search"></i>
          <h5>No products match{{ $q !== '' ? ' "' . $q . '"' : '' }}</h5>
          <p>Check the spelling, try a more general word, or browse a category.</p>
          <a href="{{ route('marketplace.index') }}" class="btn-mp">Back to the shop</a>
        </div>
      @else
        <div class="mk-grid mk-grid--4">
          @foreach($products as $product)@include('landlord.marketplace._product-card')@endforeach
        </div>
        <div class="mt-4 mp-pagination">{{ $products->withQueryString()->links('pagination::bootstrap-5') }}</div>
      @endif
    </div>
  </div>
@endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.getElementById('mkCarousel');
    if (!root) return;
    var slides = root.querySelectorAll('.mk-slide');
    var dots = root.querySelectorAll('[data-slide-to]');
    if (slides.length < 2) return;
    var i = 0, timer;
    function show(n) {
        i = (n + slides.length) % slides.length;
        slides.forEach(function (s, k) { s.classList.toggle('is-on', k === i); s.setAttribute('aria-hidden', k === i ? 'false' : 'true'); });
        dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); });
    }
    function start() {
        clearInterval(timer);
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) timer = setInterval(function () { show(i + 1); }, 5500);
    }
    dots.forEach(function (d) { d.addEventListener('click', function () { show(+d.dataset.slideTo); start(); }); });
    root.addEventListener('mouseenter', function () { clearInterval(timer); });
    root.addEventListener('mouseleave', start);
    start();
})();
</script>
@endpush
