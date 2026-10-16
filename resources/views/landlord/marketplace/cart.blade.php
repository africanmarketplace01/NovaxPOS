@extends('landlord.marketplace.layout')
@section('title', 'Your Cart')

@section('extra-css')
<style>
.cart-page { max-width: 1120px; padding-top: 1rem; padding-bottom: 3.5rem; }
.cart-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin: 1.2rem 0 1.5rem; }
.cart-heading h1 { margin: 0; font-size: clamp(1.8rem, 3vw, 2.35rem); font-weight: 750; letter-spacing: -.045em; }
.cart-heading p { margin: .25rem 0 0; color: var(--mp-muted); font-size: var(--text-sm); }
.checkout-steps { display: flex; align-items: center; gap: .6rem; color: var(--mp-muted); font-size: var(--text-xs); font-weight: 650; white-space: nowrap; }
.checkout-step { display: inline-flex; align-items: center; gap: .4rem; }
.checkout-step span { display: grid; width: 26px; height: 26px; place-items: center; border: 1px solid var(--mp-border); border-radius: 50%; background: #fff; }
.checkout-step.is-current { color: var(--mp-link); }
.checkout-step.is-current span { border-color: var(--mp-link); background: var(--mp-link); color: #fff; }
.checkout-step-line { width: 32px; height: 1px; background: var(--mp-border); }
.cart-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; align-items: start; gap: 1.25rem; }
.cart-items { overflow: hidden; }
.cart-list-head { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--mp-border); }
.cart-list-head strong { font-size: var(--text-sm); }
.cart-list-head span { color: var(--mp-muted); font-size: var(--text-xs); }
.cart-line { display: grid; grid-template-columns: 104px minmax(0, 1fr) auto; gap: 1rem; align-items: center; padding: 1.2rem 1.25rem; }
.cart-line + .cart-line { border-top: 1px solid var(--mp-border); }
.cart-line-image { display: grid; width: 104px; aspect-ratio: 1; place-items: center; overflow: hidden; border-radius: 8px; background: #f5f6f7; color: #aab3be; font-size: 1.5rem; }
.cart-line-image img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply; }
.cart-line-name { display: block; margin-bottom: .2rem; color: var(--mp-ink); font-size: var(--text-base); font-weight: 650; line-height: 1.35; }
.cart-line-name:hover { color: var(--mp-link); }
.cart-line-seller { color: var(--mp-muted); font-size: var(--text-xs); }
.cart-line-seller strong { color: var(--mp-ink); font-weight: 600; }
.cart-line-unit { margin-top: .45rem; color: var(--mp-muted); font-size: var(--text-xs); }
.cart-line-controls { display: flex; align-items: center; gap: .6rem; margin-top: .75rem; }
.cart-qty { display: flex; align-items: center; border: 1px solid var(--mp-border); border-radius: 6px; overflow: hidden; }
.cart-qty input { width: 52px; height: 34px; padding: 0 .3rem; border: 0; text-align: center; font: 600 var(--text-sm)/1 var(--mp-font, inherit); color: var(--mp-ink); }
.cart-qty button { height: 34px; padding: 0 .6rem; border: 0; border-left: 1px solid var(--mp-border); background: #fff; color: var(--mp-link); font-size: var(--text-xs); font-weight: 650; }
.cart-remove { border: 0; background: transparent; color: var(--mp-muted); font-family: inherit; font-size: var(--text-xs); font-weight: 600; line-height: 1.2; }
.cart-remove:hover { color: #b42318; }
.cart-line-total { min-width: 95px; text-align: right; }
.cart-line-total strong { display: block; color: var(--mp-ink); font-size: var(--text-base); font-weight: 750; }
.cart-line-total span { color: var(--mp-muted); font-size: var(--text-xs); }
.cart-list-foot { padding: .9rem 1.25rem; border-top: 1px solid var(--mp-border); background: #fbfcfd; }
.cart-list-foot button { padding: 0; border: 0; background: transparent; color: var(--mp-muted); font-family: inherit; font-size: var(--text-xs); font-weight: 600; line-height: 1.4; }
.cart-list-foot button:hover { color: #b42318; }
.cart-summary { position: sticky; top: 150px; padding: 1.25rem; }
.cart-summary h2 { margin: 0; font-size: var(--text-lg); font-weight: 700; }
.cart-summary-note { margin: .25rem 0 1.1rem; color: var(--mp-muted); font-size: var(--text-xs); }
.cart-summary-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin: .75rem 0; color: var(--mp-muted); font-size: var(--text-sm); }
.cart-summary-row strong { color: var(--mp-ink); font-weight: 650; }
.cart-summary-total { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--mp-border); color: var(--mp-ink); font-size: var(--text-base); font-weight: 700; }
.cart-summary-total strong { font-size: 1.3rem; font-weight: 800; }
.cart-checkout-btn { width: 100%; min-height: 48px; margin-top: .4rem; }
.cart-safe { display: flex; justify-content: center; gap: .45rem; margin: .85rem 0 0; color: var(--mp-muted); font-size: var(--text-xs); }
.cart-continue { display: inline-flex; align-items: center; gap: .45rem; margin-top: 1.1rem; color: var(--mp-link); font-size: var(--text-sm); font-weight: 650; }
.cart-empty { max-width: 700px; margin: 2rem auto 4rem; padding: 3.5rem 2rem; text-align: center; }
.cart-empty-mark { display: grid; width: 72px; height: 72px; place-items: center; margin: 0 auto 1.25rem; border-radius: 50%; background: var(--mp-tint); color: var(--mp-link); font-size: 1.5rem; }
.cart-empty h1 { margin: 0; font-size: 1.8rem; font-weight: 750; letter-spacing: -.04em; }
.cart-empty p { margin: .5rem 0 1.25rem; color: var(--mp-muted); }
@media (max-width: 767px) {
    .cart-page { padding-top: .5rem; }
    .cart-heading { align-items: flex-start; flex-direction: column; margin: 1rem 0 1.1rem; }
    .cart-layout { grid-template-columns: minmax(0, 1fr); }
    .cart-summary { position: static; }
}
@media (max-width: 480px) {
    .cart-line { grid-template-columns: 76px minmax(0, 1fr); gap: .8rem; padding: 1rem; }
    .cart-line-image { width: 76px; }
    .cart-line-total { grid-column: 2; display: flex; align-items: baseline; justify-content: space-between; width: 100%; min-width: 0; text-align: left; }
    .cart-line-total span { order: -1; }
    .cart-line-total strong { font-size: var(--text-sm); }
}
</style>
@endsection

@section('content')
<div class="container cart-page">
    <div class="mp-bc">
        <a href="{{ route('marketplace.index') }}">Home</a><span class="sep">/</span>Cart
    </div>

    @if(empty($cart))
        <section class="mk-panel cart-empty">
            <div class="cart-empty-mark"><i class="fas fa-shopping-bag" aria-hidden="true"></i></div>
            <h1>Your cart is waiting</h1>
            <p>When you find something you love, it will be right here.</p>
            <a href="{{ route('marketplace.index') }}" class="btn-mp">Explore the marketplace <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </section>
    @else
        @php
            $totalItems = collect($cart)->sum('qty');
            $total = collect($cart)->sum(fn ($item) => $item['price'] * $item['qty']);
        @endphp
        <div class="cart-heading">
            <div>
                <h1>Your cart <span style="color:var(--mp-muted);font-size:.62em;font-weight:500">({{ $totalItems }})</span></h1>
                <p>Review your items before you continue to checkout.</p>
            </div>
            <div class="checkout-steps" aria-label="Checkout progress">
                <span class="checkout-step is-current"><span>1</span> Cart</span>
                <i class="checkout-step-line" aria-hidden="true"></i>
                <span class="checkout-step"><span>2</span> Details</span>
                <i class="checkout-step-line" aria-hidden="true"></i>
                <span class="checkout-step"><span>3</span> Confirmation</span>
            </div>
        </div>

        <div class="cart-layout">
            <section class="mk-panel cart-items" aria-label="Items in your cart">
                <div class="cart-list-head">
                    <strong>Items in your bag</strong>
                    <span>{{ count($cart) }} {{ \Illuminate\Support\Str::plural('item', count($cart)) }}</span>
                </div>

                @foreach($cart as $item)
                    <article class="cart-line">
                        <div class="cart-line-image">
                            @if($item['image_url'])
                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" loading="lazy">
                            @else
                                <i class="fas fa-box" aria-hidden="true"></i>
                            @endif
                        </div>
                        <div>
                            <a class="cart-line-name" href="{{ route('marketplace.product', $item['listing_id']) }}">{{ $item['name'] }}</a>
                            <div class="cart-line-seller">Sold by <strong>{{ $item['tenant_name'] }}</strong></div>
                            <div class="cart-line-unit">{{ $currency }} {{ number_format($item['price'], 2) }} each</div>
                            <div class="cart-line-controls">
                                <form action="{{ route('marketplace.cart.update') }}" method="POST" class="cart-qty">
                                    @csrf
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="listing_id" value="{{ $item['listing_id'] }}">
                                    <label class="visually-hidden" for="qty-{{ $item['listing_id'] }}">Quantity for {{ $item['name'] }}</label>
                                    <input id="qty-{{ $item['listing_id'] }}" type="number" name="qty" value="{{ $item['qty'] }}" min="1" max="999" inputmode="numeric">
                                    <button type="submit">Update</button>
                                </form>
                                <form action="{{ route('marketplace.cart.update') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="listing_id" value="{{ $item['listing_id'] }}">
                                    <button type="submit" class="cart-remove"><i class="far fa-trash-alt me-1" aria-hidden="true"></i>Remove</button>
                                </form>
                            </div>
                        </div>
                        <div class="cart-line-total">
                            <strong>{{ $currency }} {{ number_format($item['price'] * $item['qty'], 2) }}</strong>
                            <span>Line total</span>
                        </div>
                    </article>
                @endforeach

                <div class="cart-list-foot">
                    <form action="{{ route('marketplace.cart.update') }}" method="POST" onsubmit="return confirm('Remove all items from your cart?')">
                        @csrf
                        <input type="hidden" name="action" value="clear">
                        <button type="submit"><i class="far fa-trash-alt me-1" aria-hidden="true"></i>Clear cart</button>
                    </form>
                </div>
            </section>

            <aside class="mk-panel cart-summary" aria-labelledby="cart-summary-title">
                <h2 id="cart-summary-title">Order summary</h2>
                <p class="cart-summary-note">Shipping and any applicable fees are shown at checkout.</p>
                <div class="cart-summary-row">
                    <span>Subtotal ({{ $totalItems }} {{ \Illuminate\Support\Str::plural('item', $totalItems) }})</span>
                    <strong>{{ $currency }} {{ number_format($total, 2) }}</strong>
                </div>
                <div class="cart-summary-row">
                    <span>Delivery</span>
                    <span>Calculated next</span>
                </div>
                <div class="cart-summary-row cart-summary-total">
                    <span>Estimated total</span>
                    <strong>{{ $currency }} {{ number_format($total, 2) }}</strong>
                </div>
                <a href="{{ route('marketplace.checkout') }}" class="btn-mp cart-checkout-btn">
                    Continue to checkout <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
                <p class="cart-safe"><i class="fas fa-lock" aria-hidden="true"></i> Secure checkout</p>
                <a href="{{ route('marketplace.index') }}" class="cart-continue"><i class="fas fa-arrow-left" aria-hidden="true"></i> Continue shopping</a>
            </aside>
        </div>
    @endif
</div>
@endsection
