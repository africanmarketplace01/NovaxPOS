@extends('landlord.marketplace.layout')
@php $shopName = $shopInfo->tenant_store_name ?: $shopInfo->tenant_name; @endphp
@section('title', $shopName)
@section('meta-desc', 'Shop ' . $shopName . ' on ' . $mkName . ': ' . $shopInfo->product_count . ' products, pay on delivery.')

@section('extra-css')
<style>
.st-head { padding: 1.3rem 1.4rem; display: flex; align-items: center; gap: 1.1rem; flex-wrap: wrap; margin-top: .8rem; }
.st-logo { width: 76px; height: 76px; border-radius: 50%; flex-shrink: 0; border: 1px solid var(--mp-border); background: #fff; overflow: hidden; display: flex; align-items: center; justify-content: center; }
.st-logo img { width: 100%; height: 100%; object-fit: contain; padding: 6px; }
.st-logo span { font-size: 1.9rem; font-weight: 800; color: var(--mp-link); background: var(--mp-tint-2); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
.st-name { font-size: 1.5rem; font-weight: 800; margin: 0; letter-spacing: -.01em; }
.st-meta { display: flex; gap: 1rem; flex-wrap: wrap; font-size: var(--text-sm); color: var(--mp-muted); margin-top: .25rem; }
.st-meta .ok { color: var(--mp-ok); font-weight: 600; }
.st-search { margin-left: auto; display: flex; border: 1px solid #d4d4d8; border-radius: 6px; overflow: hidden; min-width: 260px; height: 40px; }
.st-search input { border: 0; padding: 0 .8rem; flex: 1; outline: none; font-family: inherit; font-size: var(--text-sm); min-width: 0; }
.st-search button { border: 0; background: var(--mp-ink); color: #fff; padding: 0 .9rem; }
@media (max-width: 767px) { .st-search { margin-left: 0; width: 100%; min-width: 0; } }
.st-tabs { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; padding: .9rem 0 .2rem; }
.st-tabs::-webkit-scrollbar { display: none; }
</style>
@endsection

@section('content')
<div class="container">
  <div class="mp-bc">
    <a href="{{ route('marketplace.index') }}">Home</a><span class="sep">/</span>Stores<span class="sep">/</span>{{ Str::limit($shopName, 40) }}
  </div>

  <section class="mk-panel st-head">
    <div class="st-logo">
      @if(!empty($shopInfo->logo_url))
        <img src="{{ $shopInfo->logo_url }}" alt="{{ $shopName }} logo">
      @else
        <span>{{ strtoupper(substr($shopName, 0, 1)) }}</span>
      @endif
    </div>
    <div>
      <h1 class="st-name">{{ $shopName }}</h1>
      <div class="st-meta">
        <span class="ok"><i class="fas fa-check-circle"></i> Verified store</span>
        <span>{{ number_format($shopInfo->product_count) }} {{ Str::plural('product', $shopInfo->product_count) }}</span>
        @if($categories->count())<span>{{ $categories->take(3)->implode(' · ') }}</span>@endif
      </div>
    </div>
    <form class="st-search" method="GET" action="{{ route('marketplace.shop', $shopInfo->tenant_id) }}" role="search">
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Search this store" aria-label="Search this store">
      <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
    </form>
  </section>

  @if($categories->count() > 1)
    <nav class="st-tabs" aria-label="Store categories">
      <a href="{{ route('marketplace.shop', $shopInfo->tenant_id) }}" class="shop-cat-tab {{ !request('category') ? 'active' : '' }}">All products</a>
      @foreach($categories as $cat)
        <a href="{{ route('marketplace.shop', [$shopInfo->tenant_id, 'category' => $cat]) }}" class="shop-cat-tab {{ request('category') === $cat ? 'active' : '' }}">{{ $cat }}</a>
      @endforeach
    </nav>
  @endif

  <div class="mk-results-bar mt-3">
    <span class="count">
      {{ number_format($products->total()) }} {{ Str::plural('product', $products->total()) }}
      @if(request('category')) in <strong style="color:var(--mp-ink)">{{ request('category') }}</strong>@endif
      @if(request('q')) matching <strong style="color:var(--mp-ink)">"{{ request('q') }}"</strong>@endif
    </span>
    <label class="d-flex align-items-center gap-2" style="font-size:var(--text-sm)">
      <span class="text-nowrap">Sort by</span>
      <select class="form-select form-select-sm" style="width:auto" onchange="location.href=this.value">
        @foreach(['newest' => 'Newest', 'discount' => 'Biggest discount', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name (A–Z)'] as $key => $label)
          <option value="{{ route('marketplace.shop', array_merge([$shopInfo->tenant_id], request()->except('sort', 'page'), ['sort' => $key])) }}" @selected(request('sort', 'newest') === $key)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
  </div>

  @if($products->isEmpty())
    <div class="empty-state">
      <i class="fas fa-box-open"></i>
      <h5>Nothing here yet</h5>
      <p>This store has no products matching your filters.</p>
      <a href="{{ route('marketplace.shop', $shopInfo->tenant_id) }}" class="btn-mp">See all products</a>
    </div>
  @else
    <div class="mk-grid">
      @foreach($products as $product)@include('landlord.marketplace._product-card')@endforeach
    </div>
    <div class="mt-4 mp-pagination">{{ $products->withQueryString()->links('pagination::bootstrap-5') }}</div>
  @endif
</div>
@endsection
