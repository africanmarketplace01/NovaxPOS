{{--
    One product tile. Layout-agnostic: the page decides the grid or shelf.
    $product: MarketplaceListing
--}}
@php
    $mkStore = $product->tenant_store_name ?: $product->tenant_name;
    $mkSale  = $product->promo_price && $product->price > 0 && $product->promo_price < $product->price;
    $mkPct   = $mkSale ? (int) round((1 - $product->promo_price / $product->price) * 100) : 0;
    $mkUrl   = route('marketplace.product', $product->id);
    $mkInStock = $product->in_stock && $product->stock_qty > 0;
@endphp
<article class="pcard">
  @if($mkPct > 0)<span class="pcard-badge">-{{ $mkPct }}%</span>@endif

  <a href="{{ $mkUrl }}" class="pcard-img-wrap" tabindex="-1" aria-hidden="true">
    @if($product->image_url)
      <img src="{{ $product->image_url }}" alt="" class="pcard-img" loading="lazy">
    @else
      <span class="pcard-placeholder"><i class="fas fa-image"></i></span>
    @endif
  </a>

  <div class="pcard-body">
    <a href="{{ $mkUrl }}" class="pcard-name">{{ $product->name }}</a>

    <div class="pcard-price-row">
      <span class="pcard-price">{{ $product->currency }} {{ number_format($mkSale ? $product->promo_price : $product->price, 2) }}</span>
      @if($mkSale)<span class="pcard-original">{{ $product->currency }} {{ number_format($product->price, 2) }}</span>@endif
    </div>

    @if(!$mkInStock)
      <div class="pcard-out">Out of stock</div>
    @elseif($product->stock_qty <= 5)
      <div class="pcard-stock-low">Only {{ $product->stock_qty }} left</div>
    @endif

    <div class="pcard-store">{{ $mkStore }}</div>

    <form action="{{ route('marketplace.cart.add') }}" method="POST" data-atc-form class="mt-auto">
      @csrf
      <input type="hidden" name="listing_id" value="{{ $product->id }}">
      <input type="hidden" name="qty" value="1">
      <button type="submit" class="pcard-atc" data-atc @disabled(!$mkInStock) aria-label="Add {{ $product->name }} to cart">
        {{ $mkInStock ? 'Add to cart' : 'Unavailable' }}
      </button>
    </form>
  </div>
</article>
