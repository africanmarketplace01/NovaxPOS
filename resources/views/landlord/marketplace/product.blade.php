@extends('landlord.marketplace.layout')
@section('title', $product->name)
@section('meta-desc', Str::limit(trim(strip_tags($product->description ?? '')) ?: $product->name, 155))

@php
    $gallery = $product->galleryImages();
    $avg     = $ratingStats['average'] ?? 0;
    $rcount  = $ratingStats['count'] ?? 0;
    $store   = $product->tenant_store_name ?: $product->tenant_name;
    $onSale  = $product->promo_price && $product->price > 0 && $product->promo_price < $product->price;
    $savePct = $onSale ? (int) round((1 - $product->promo_price / $product->price) * 100) : 0;
    $inStock = $product->in_stock && $product->stock_qty > 0;
@endphp

@section('extra-css')
<style>
.pd { display: grid; grid-template-columns: minmax(0, 5fr) minmax(0, 4fr) minmax(0, 3fr); gap: 1rem; align-items: start; margin-top: .8rem; }
@media (max-width: 1199px) { .pd { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } .pd-side { grid-column: 1 / -1; } }
@media (max-width: 767px) { .pd { grid-template-columns: minmax(0, 1fr); } }
.pd-gallery { padding: .9rem; }
.pd-main { aspect-ratio: 1 / 1; border-radius: 6px; overflow: hidden; background: #fafafa; }
.pd-main img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
.pd-main .ph { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #d4d4d8; }
.pd-thumbs { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .6rem; }
.pd-thumb { width: 64px; height: 64px; border-radius: 6px; border: 2px solid var(--mp-border); object-fit: cover; cursor: pointer; padding: 0; background: #fff; }
.pd-thumb.active { border-color: var(--mp-primary); }
.pd-info { padding: 1.2rem 1.3rem; }
.pd-store { font-size: var(--text-sm); color: var(--mp-muted); }
.pd-store a { color: var(--mp-link); font-weight: 600; }
.pd h1 { font-size: 1.45rem; font-weight: 700; line-height: 1.3; margin: .35rem 0 .45rem; letter-spacing: -.01em; }
.pd-rating { font-size: var(--text-sm); color: var(--mp-muted); display: flex; align-items: center; gap: .4rem; }
.pd-rating .stars { color: #f5a623; letter-spacing: 1px; }
.pd-rating a { color: var(--mp-link); font-weight: 600; }
.pd-price { border-top: 1px solid var(--mp-border); margin-top: .9rem; padding-top: .9rem; }
.pd-price-now { font-size: 2rem; font-weight: 800; line-height: 1.1; }
.pd-price-was { color: var(--mp-muted); font-size: var(--text-base); text-decoration: line-through; margin-right: .4rem; }
.pd-save { background: var(--mp-tint-2); color: var(--mp-link); font-weight: 800; font-size: var(--text-sm); padding: .15rem .5rem; border-radius: 4px; }
.pd-stock { margin-top: .6rem; font-size: var(--text-sm); font-weight: 600; }
.pd-stock.in { color: var(--mp-ok); }
.pd-stock.low { color: var(--mp-sale); }
.pd-stock.out { color: var(--mp-muted); }
.pd-buy { display: flex; gap: .6rem; align-items: stretch; margin-top: 1rem; flex-wrap: wrap; }
.qty-control { display: flex; align-items: center; border: 1px solid #d4d4d8; border-radius: 6px; overflow: hidden; }
.qty-btn { width: 40px; height: 46px; background: #fafafa; border: 0; font-size: var(--text-lg); color: var(--mp-ink); }
.qty-btn:hover { background: var(--mp-tint-2); color: var(--mp-link); }
.qty-inp { width: 54px; height: 46px; border: 0; border-left: 1px solid #e4e4e7; border-right: 1px solid #e4e4e7; text-align: center; font-weight: 700; font-family: inherit; -moz-appearance: textfield; }
.qty-inp::-webkit-outer-spin-button, .qty-inp::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.pd-buy .btn-mp { flex: 1; min-height: 46px; font-size: var(--text-base); }
.pd-side-box { padding: 1rem 1.1rem; }
.pd-side-box + .pd-side-box { margin-top: .9rem; }
.pd-side-box h2 { font-size: var(--text-xs); font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--mp-muted); margin: 0 0 .7rem; }
.pd-fact { display: flex; gap: .7rem; font-size: var(--text-sm); padding: .45rem 0; }
.pd-fact i { color: var(--mp-link); width: 1.2rem; text-align: center; margin-top: .2rem; }
.pd-fact strong { display: block; font-size: var(--text-sm); }
.pd-fact small { color: var(--mp-muted); }
.pd-seller { display: flex; align-items: center; gap: .7rem; }
.pd-seller-av { width: 46px; height: 46px; border-radius: 50%; background: var(--mp-tint-2); color: var(--mp-link); font-weight: 800; font-size: var(--text-lg); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.pd-panel { padding: 1.2rem 1.3rem; margin-top: 1rem; }
.pd-panel-title { font-size: var(--text-lg); font-weight: 800; margin: 0 0 .9rem; }
.prod-desc { font-size: var(--text-sm); color: #3f3f46; line-height: 1.75; }
.rv-submit { background: var(--mp-primary); color: var(--mp-on-primary); border: 0; border-radius: 6px; padding: .6rem 1.3rem; font-weight: 700; font-family: inherit; }
.rv-submit:hover { background: var(--mp-light); }
/* Reviews */
.rv-summary { display: flex; gap: 1.75rem; flex-wrap: wrap; align-items: center; padding-bottom: 1.1rem; border-bottom: 1px solid var(--mp-border); margin-bottom: 1.1rem; }
.rv-score { text-align: center; min-width: 120px; }
.rv-score-num { font-size: 2.6rem; font-weight: 800; line-height: 1; }
.rv-score .stars { color: #f5a623; letter-spacing: 2px; margin: .3rem 0 .15rem; }
.rv-score-count { font-size: var(--text-xs); color: var(--mp-muted); }
.rv-bars { flex: 1; min-width: 220px; }
.rv-bar-row { display: flex; align-items: center; gap: .6rem; font-size: var(--text-xs); color: var(--mp-muted); margin-bottom: .3rem; }
.rv-bar-row .lbl { width: 34px; text-align: right; }
.rv-bar-track { flex: 1; height: 8px; background: #f1f1f3; border-radius: 6px; overflow: hidden; }
.rv-bar-fill { height: 100%; background: #f5a623; display: block; }
.rv-bar-row .cnt { width: 26px; }
.rv-item { padding: 1rem 0; border-bottom: 1px solid var(--mp-border); }
.rv-item:last-child { border-bottom: 0; }
.rv-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .3rem; }
.rv-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--mp-tint-2); color: var(--mp-link); display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0; }
.rv-name { font-weight: 700; font-size: var(--text-sm); }
.rv-date { font-size: var(--text-xs); color: var(--mp-muted); }
.rv-stars { color: #f5a623; font-size: var(--text-xs); letter-spacing: 1px; }
.rv-verified { font-size: .68rem; color: var(--mp-ok); font-weight: 700; margin-left: .35rem; }
.rv-title { font-weight: 700; font-size: var(--text-sm); margin: .35rem 0 .2rem; }
.rv-comment { font-size: var(--text-sm); color: #3f3f46; line-height: 1.65; }
.rv-empty { text-align: center; color: var(--mp-muted); padding: 1.2rem 0; font-size: var(--text-sm); }
.star-input { display: inline-flex; flex-direction: row-reverse; gap: .15rem; }
.star-input input { display: none; }
.star-input label { font-size: 1.6rem; color: #e4e4e7; cursor: pointer; }
.star-input input:checked ~ label, .star-input label:hover, .star-input label:hover ~ label { color: #f5a623; }
.rv-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
@media (max-width: 575px) { .rv-form-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container">
  <div class="mp-bc">
    <a href="{{ route('marketplace.index') }}">Home</a>
    @if($product->category)<span class="sep">/</span><a href="{{ route('marketplace.index', ['category' => $product->category]) }}">{{ $product->category }}</a>@endif
    <span class="sep">/</span>{{ Str::limit($product->name, 50) }}
  </div>

  <div class="pd">
    {{-- Gallery --}}
    <div class="mk-panel pd-gallery">
      <div class="pd-main">
        @if(count($gallery))
          <img src="{{ $gallery[0] }}" alt="{{ $product->name }}" id="galleryMain">
        @else
          <div class="ph"><i class="fas fa-image"></i></div>
        @endif
      </div>
      @if(count($gallery) > 1)
        <div class="pd-thumbs">
          @foreach($gallery as $i => $img)
            <button type="button" class="pd-thumb {{ $i === 0 ? 'active' : '' }}" onclick="setMainImage(this, @js($img))" aria-label="Show image {{ $i + 1 }}">
              <img src="{{ $img }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:4px">
            </button>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Buy box --}}
    <div class="mk-panel pd-info">
      <div class="pd-store">Sold by <a href="{{ route('marketplace.shop', $product->tenant_id) }}">{{ $store }}</a></div>
      <h1>{{ $product->name }}</h1>
      @if($rcount)
        <div class="pd-rating">
          <span class="stars">@for($s = 1; $s <= 5; $s++)<i class="fa{{ $s <= round($avg) ? 's' : 'r' }} fa-star"></i>@endfor</span>
          {{ number_format($avg, 1) }} <a href="#reviews">{{ $rcount }} {{ Str::plural('rating', $rcount) }}</a>
        </div>
      @else
        <div class="pd-rating"><a href="#reviews">Be the first to rate this product</a></div>
      @endif

      <div class="pd-price">
        <div class="pd-price-now">{{ $product->currency }} {{ number_format($onSale ? $product->promo_price : $product->price, 2) }}</div>
        @if($onSale)
          <div class="mt-1"><span class="pd-price-was">{{ $product->currency }} {{ number_format($product->price, 2) }}</span><span class="pd-save">-{{ $savePct }}%</span></div>
        @endif
        @if(!$inStock)
          <div class="pd-stock out"><i class="fas fa-times-circle"></i> Out of stock</div>
        @elseif($product->stock_qty <= 5)
          <div class="pd-stock low"><i class="fas fa-fire"></i> Only {{ $product->stock_qty }} left. Order soon.</div>
        @else
          <div class="pd-stock in"><i class="fas fa-check-circle"></i> In stock</div>
        @endif
      </div>

      <form action="{{ route('marketplace.cart.add') }}" method="POST" id="atcForm" data-atc-form>
        @csrf
        <input type="hidden" name="listing_id" value="{{ $product->id }}">
        <div class="pd-buy">
          @if($inStock)
            <div class="qty-control">
              <button type="button" class="qty-btn" onclick="adjustQty(-1)" aria-label="Decrease quantity">−</button>
              <input type="number" name="qty" id="qtyInput" value="1" min="1" max="{{ $product->stock_qty ?: 999 }}" class="qty-inp" aria-label="Quantity">
              <button type="button" class="qty-btn" onclick="adjustQty(1)" aria-label="Increase quantity">+</button>
            </div>
          @endif
          <button type="submit" class="btn-mp" data-atc @disabled(!$inStock)>
            <i class="fas fa-cart-plus"></i> {{ $inStock ? 'Add to cart' : 'Out of stock' }}
          </button>
        </div>
      </form>
    </div>

    {{-- Delivery + seller --}}
    <aside class="pd-side">
      <div class="mk-panel pd-side-box">
        <h2>Delivery &amp; payment</h2>
        <div class="pd-fact"><i class="fas fa-motorcycle"></i><span><strong>Choose your courier</strong><small>Pick a delivery partner and see the fee at checkout.</small></span></div>
        <div class="pd-fact"><i class="fas fa-hand-holding-usd"></i><span><strong>Pay on delivery</strong><small>Pay cash when your order arrives.</small></span></div>
        @if(count($mkPay) > 1)
          <div class="pd-fact"><i class="fas fa-mobile-alt"></i><span><strong>Or pay online</strong><small>{{ implode(', ', array_slice(array_keys($mkPay), 1)) }}.</small></span></div>
        @endif
      </div>
      <div class="mk-panel pd-side-box">
        <h2>Seller</h2>
        <a href="{{ route('marketplace.shop', $product->tenant_id) }}" class="pd-seller">
          <span class="pd-seller-av">{{ strtoupper(substr($store, 0, 1)) }}</span>
          <span><strong class="d-block">{{ $store }}</strong><small style="color:var(--mp-ok);font-weight:600"><i class="fas fa-check-circle"></i> Verified store</small></span>
        </a>
        <a href="{{ route('marketplace.shop', $product->tenant_id) }}" class="btn-mp-outline w-100 mt-3" style="padding:.45rem">Visit store</a>
      </div>
    </aside>
  </div>

  {{-- Description --}}
  <section class="mk-panel pd-panel" id="description">
    <h2 class="pd-panel-title">Product details</h2>
    @if($product->description)
      <div class="prod-desc">{!! strip_tags($product->description, '<p><br><b><strong><i><em><u><ul><ol><li><span><h3><h4><h5><h6>') !!}</div>
    @else
      <p class="mb-0" style="color:var(--mp-muted)">The seller hasn't added a description yet.</p>
    @endif
  </section>

  {{-- Reviews --}}
  <section class="mk-panel pd-panel" id="reviews">
    <h2 class="pd-panel-title">Customer reviews</h2>
    <div class="rv-summary">
      <div class="rv-score">
        <div class="rv-score-num">{{ $rcount ? number_format($avg, 1) : '–' }}</div>
        <div class="stars">@for($s = 1; $s <= 5; $s++)<i class="fa{{ $s <= round($avg) ? 's' : 'r' }} fa-star"></i>@endfor</div>
        <div class="rv-score-count">{{ $rcount }} {{ Str::plural('review', $rcount) }}</div>
      </div>
      <div class="rv-bars">
        @for($s = 5; $s >= 1; $s--)
          @php $bc = $ratingStats['breakdown'][$s] ?? 0; $pct = $rcount ? round($bc / $rcount * 100) : 0; @endphp
          <div class="rv-bar-row">
            <span class="lbl">{{ $s }} <i class="fas fa-star" style="font-size:.6rem;color:#f5a623"></i></span>
            <span class="rv-bar-track"><span class="rv-bar-fill" style="width:{{ $pct }}%"></span></span>
            <span class="cnt">{{ $bc }}</span>
          </div>
        @endfor
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <h3 style="font-size:var(--text-base);font-weight:800">Write a review</h3>
        <form action="{{ route('marketplace.product.review', $product->id) }}" method="POST" class="rv-form">
          @csrf
          <div class="mb-2">
            <div class="star-input" role="radiogroup" aria-label="Your rating">
              @for($s = 5; $s >= 1; $s--)
                <input type="radio" name="rating" id="star{{ $s }}" value="{{ $s }}" @if($s === 5) required @endif><label for="star{{ $s }}" title="{{ $s }} {{ Str::plural('star', $s) }}">&#9733;</label>
              @endfor
            </div>
          </div>
          <div class="rv-form-grid mb-2">
            <input type="text" name="reviewer_name" class="form-control" placeholder="Your name *" maxlength="80" required aria-label="Your name">
            <input type="email" name="reviewer_email" class="form-control" placeholder="Email (not shown)" maxlength="120" aria-label="Email">
          </div>
          <input type="text" name="title" class="form-control mb-2" placeholder="Review title (optional)" maxlength="120" aria-label="Review title">
          <textarea name="comment" class="form-control mb-2" rows="3" placeholder="What did you like or dislike? *" maxlength="2000" required aria-label="Your review"></textarea>
          @error('rating')<div class="text-danger small mb-2">Please choose a star rating.</div>@enderror
          <button type="submit" class="rv-submit">Submit review</button>
        </form>
      </div>
      <div class="col-lg-7">
        @forelse($reviews as $rv)
          <div class="rv-item">
            <div class="rv-head">
              <div class="rv-avatar">{{ strtoupper(Str::substr($rv->reviewer_name, 0, 1)) }}</div>
              <div>
                <div class="rv-name">{{ $rv->reviewer_name }}@if($rv->is_verified)<span class="rv-verified"><i class="fas fa-check"></i> Verified purchase</span>@endif</div>
                <div class="rv-date">{{ $rv->created_at?->format('M j, Y') }}</div>
              </div>
              <div class="ms-auto rv-stars">@for($s = 1; $s <= 5; $s++)<i class="fa{{ $s <= $rv->rating ? 's' : 'r' }} fa-star"></i>@endfor</div>
            </div>
            @if($rv->title)<div class="rv-title">{{ $rv->title }}</div>@endif
            @if($rv->comment)<div class="rv-comment">{{ $rv->comment }}</div>@endif
          </div>
        @empty
          <div class="rv-empty">No reviews yet. Bought this? Tell other shoppers what you think.</div>
        @endforelse
      </div>
    </div>
  </section>

  @if($related->count())
    <section class="mk-shelf mk-section" aria-labelledby="relatedTitle">
      <div class="mk-shelf-head">
        <h2 id="relatedTitle">You may also like</h2>
        <a href="{{ route('marketplace.shop', $product->tenant_id) }}">More from {{ Str::limit($store, 24) }} <i class="fas fa-angle-right"></i></a>
      </div>
      <div class="mk-shelf-body">
        <div class="mk-grid">
          @foreach($related as $rel)@include('landlord.marketplace._product-card', ['product' => $rel])@endforeach
        </div>
      </div>
    </section>
  @endif
</div>
@endsection

@push('scripts')
<script>
function adjustQty(delta) {
    var inp = document.getElementById('qtyInput');
    if (!inp) return;
    var v = (parseInt(inp.value, 10) || 1) + delta;
    inp.value = Math.max(parseInt(inp.min, 10) || 1, Math.min(parseInt(inp.max, 10) || 999, v));
}
function setMainImage(thumb, src) {
    var main = document.getElementById('galleryMain');
    if (main) main.src = src;
    document.querySelectorAll('.pd-thumb').forEach(function (t) { t.classList.remove('active'); });
    thumb.classList.add('active');
}
</script>
@endpush
