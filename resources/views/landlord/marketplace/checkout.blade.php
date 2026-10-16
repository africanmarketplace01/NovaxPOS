@extends('landlord.marketplace.layout')
@section('title', 'Checkout')

@section('extra-css')
<style>
.checkout-page { max-width: 1120px; padding-top: 1rem; padding-bottom: 3.5rem; }
.checkout-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; margin: 1.2rem 0 1.5rem; }
.checkout-heading h1 { margin: 0; font-size: clamp(1.8rem, 3vw, 2.35rem); font-weight: 750; letter-spacing: -.045em; }
.checkout-heading p { margin: .25rem 0 0; color: var(--mp-muted); font-size: var(--text-sm); }
.checkout-steps { display: flex; align-items: center; gap: .6rem; color: var(--mp-muted); font-size: var(--text-xs); font-weight: 650; white-space: nowrap; }
.checkout-step { display: inline-flex; align-items: center; gap: .4rem; }
.checkout-step span { display: grid; width: 26px; height: 26px; place-items: center; border: 1px solid var(--mp-border); border-radius: 50%; background: #fff; }
.checkout-step.is-complete, .checkout-step.is-current { color: var(--mp-link); }
.checkout-step.is-complete span { border-color: var(--mp-link); background: var(--mp-link); color: #fff; }
.checkout-step.is-current span { border-color: var(--mp-primary); background: var(--mp-primary); color: var(--mp-on-primary); }
.checkout-step-line { width: 32px; height: 1px; background: var(--mp-border); }
.checkout-columns { align-items: start; }
.checkout-panel { padding: 1.4rem; margin-bottom: 1rem; border: 1px solid var(--mp-border); border-radius: 10px; background: #fff; box-shadow: var(--mp-shadow); }
.checkout-panel:last-child { margin-bottom: 0; }
.checkout-panel h2 { display: flex; align-items: center; gap: .65rem; margin: 0 0 1.15rem; font-size: var(--text-lg); font-weight: 700; }
.checkout-panel h2 i { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 50%; background: var(--mp-tint); color: var(--mp-link); font-size: .9rem; }
.checkout-panel .form-label { margin-bottom: .4rem; color: var(--mp-ink); font-size: var(--text-xs); font-weight: 650; }
.checkout-panel .form-control { min-height: 44px; padding: .6rem .75rem; border-color: #d7dee7; border-radius: 6px; font-size: var(--text-sm); }
.checkout-panel textarea.form-control { min-height: auto; }
.checkout-panel .form-control:focus { border-color: var(--mp-link); }
.checkout-guest { display: flex; align-items: center; justify-content: space-between; gap: .8rem; margin-bottom: 1rem; padding: 1rem 1.2rem; border: 1px solid var(--mp-border); border-radius: 9px; background: #fff; font-size: var(--text-sm); }
.checkout-guest p { margin: 0; color: var(--mp-muted); }
.checkout-guest .btn { flex-shrink: 0; font-size: var(--text-xs); }
.checkout-opt { border: 1px solid var(--mp-border) !important; border-radius: 7px !important; background: #fff; transition: border-color .15s, background .15s; }
.checkout-opt.selected { border-color: var(--mp-link) !important; background: var(--mp-tint); }
.checkout-opt input { flex-shrink: 0; }
.checkout-opt-name { color: var(--mp-ink); font-size: var(--text-sm); font-weight: 650; }
.checkout-opt-meta { color: var(--mp-muted); font-size: var(--text-xs); }
.checkout-remember { padding: 1rem; border: 1px dashed var(--mp-border); border-radius: 7px; background: #fafbfc; }
.checkout-summary { position: sticky; top: 150px; padding: 1.35rem; }
.checkout-summary h2 { margin: 0; font-size: var(--text-lg); font-weight: 700; }
.checkout-summary-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1rem; }
.checkout-summary-count { color: var(--mp-muted); font-size: var(--text-xs); }
.checkout-summary-item { display: grid; grid-template-columns: 48px minmax(0, 1fr) auto; gap: .65rem; align-items: center; margin: .65rem 0; }
.checkout-summary-thumb { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 6px; overflow: hidden; background: #f3f5f7; color: #aab3be; }
.checkout-summary-thumb img { width: 100%; height: 100%; object-fit: contain; }
.checkout-summary-item-name { display: block; color: var(--mp-ink); font-size: var(--text-xs); font-weight: 600; line-height: 1.35; }
.checkout-summary-item-meta { display: block; color: var(--mp-muted); font-size: .75rem; }
.checkout-summary-item-price { color: var(--mp-ink); font-size: var(--text-xs); font-weight: 650; white-space: nowrap; }
.checkout-summary-rule { margin: 1rem 0; border-color: var(--mp-border); opacity: 1; }
.checkout-summary-row { display: flex; justify-content: space-between; gap: .75rem; margin: .65rem 0; color: var(--mp-muted); font-size: var(--text-sm); }
.checkout-summary-total { padding-top: .85rem; margin-top: .85rem; border-top: 1px solid var(--mp-border); color: var(--mp-ink); font-size: var(--text-base); font-weight: 700; }
.checkout-summary-total strong { color: var(--mp-link); font-size: 1.3rem; font-weight: 800; }
.checkout-place-order { width: 100%; min-height: 49px; margin-top: .55rem; font-size: var(--text-sm); }
.checkout-security { margin: .85rem 0 0; color: var(--mp-muted); text-align: center; font-size: var(--text-xs); }
.checkout-back { display: block; margin-top: .9rem; color: var(--mp-link); text-align: center; font-size: var(--text-sm); font-weight: 650; }
@media (max-width: 991px) { .checkout-summary { position: static; } }
@media (max-width: 767px) {
    .checkout-heading { align-items: flex-start; flex-direction: column; margin: 1rem 0 1.1rem; }
    .checkout-panel { padding: 1.1rem; }
}
@media (max-width: 420px) {
    .checkout-steps { width: 100%; justify-content: space-between; gap: .25rem; font-size: .72rem; }
    .checkout-step { gap: .25rem; }
    .checkout-step-line { width: 12px; }
    .checkout-guest { align-items: flex-start; flex-direction: column; }
}
</style>
@endsection

@section('content')
@php $marketCurrency = $settings['currency'] ?? 'USD'; @endphp
<div class="container checkout-page">
    <div class="mp-bc">
        <a href="{{ route('marketplace.index') }}">Home</a><span class="sep">/</span>
        <a href="{{ route('marketplace.cart') }}">Cart</a><span class="sep">/</span>Checkout
    </div>
    <div class="checkout-heading">
        <div>
            <h1>Checkout</h1>
            <p>Delivery details, then choose how you’d like to pay.</p>
        </div>
        <div class="checkout-steps" aria-label="Checkout progress">
            <span class="checkout-step is-complete"><span><i class="fas fa-check" aria-hidden="true"></i></span> Cart</span>
            <i class="checkout-step-line" aria-hidden="true"></i>
            <span class="checkout-step is-current"><span>2</span> Details</span>
            <i class="checkout-step-line" aria-hidden="true"></i>
            <span class="checkout-step"><span>3</span> Confirmation</span>
        </div>
    </div>

    @if($errors->any())
    <div class="mp-flash mp-flash-error" role="alert">
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
        <ul class="mb-0" style="padding-left:1.25rem">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('marketplace.order.place') }}" method="POST" id="checkoutForm">
        @csrf
        <div class="row g-4 checkout-columns">
            {{-- Left column --}}
            <div class="col-lg-7">
                {{-- Sign-in prompt for guests --}}
                @guest('customer')
                <div class="checkout-guest">
                    <p>
                        <i class="fas fa-user-circle me-1"></i> Have an account? Sign in for faster checkout &amp; order tracking.
                    </p>
                    <a href="{{ route('customer.login') }}" class="btn btn-sm btn-outline-primary">Sign in</a>
                </div>
                @endguest

                {{-- Customer details --}}
                <div class="checkout-panel">
                    <h2><i class="fas fa-user" aria-hidden="true"></i>Your details
                        @auth('customer')<span class="badge bg-success ms-2" style="font-weight:600"><i class="fas fa-check fa-xs"></i> Signed in</span>@endauth
                    </h2>
                    <div class="mb-3">
                        <label for="customer-name" class="form-label">Full name <span aria-hidden="true">*</span></label>
                        <input id="customer-name" type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $customer->name ?? '') }}" autocomplete="name" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="customer-email" class="form-label">Email</label>
                            <input id="customer-email" type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $customer->email ?? '') }}" autocomplete="email">
                        </div>
                        <div class="col-md-6">
                            <label for="customer-phone" class="form-label">Phone <span aria-hidden="true">*</span></label>
                            <input id="customer-phone" type="tel" name="customer_phone" class="form-control" value="{{ old('customer_phone', $customer->phone ?? '') }}" autocomplete="tel" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="customer-address" class="form-label">Delivery address <span aria-hidden="true">*</span></label>
                        <textarea id="customer-address" name="customer_address" class="form-control" rows="3" autocomplete="street-address" required placeholder="Street, city, country…">{{ old('customer_address', $customer->address ?? '') }}</textarea>
                    </div>

                    {{-- Create account (guests only) --}}
                    @guest('customer')
                    <div class="mt-3 checkout-remember">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="create_account" value="1" id="createAccount"
                                   onchange="document.getElementById('acctPwdWrap').style.display=this.checked?'block':'none'">
                            <label class="form-check-label fw-semibold" for="createAccount" style="font-size:var(--text-sm)">
                                Create an account for faster checkout &amp; to track this order
                            </label>
                        </div>
                        <div id="acctPwdWrap" style="display:none" class="mt-2">
                            <label for="account-password" class="form-label fw-semibold" style="font-size:var(--text-sm)">Choose a password</label>
                            <input id="account-password" type="password" name="account_password" class="form-control" minlength="6" autocomplete="new-password" placeholder="At least 6 characters">
                            <div class="form-text" style="font-size:var(--text-xs)">We'll use your email &amp; phone above for your account.</div>
                        </div>
                    </div>
                    @endguest
                    <div class="mt-3">
                        <label for="order-note" class="form-label">Order note <span class="text-muted">(optional)</span></label>
                        <textarea id="order-note" name="note" class="form-control" rows="2" placeholder="Special instructions…">{{ old('note') }}</textarea>
                    </div>
                </div>

                {{-- Delivery agency --}}
                <div class="checkout-panel">
                    <h2><i class="fas fa-truck" aria-hidden="true"></i>Delivery</h2>
                    @if($agencies->isEmpty())
                        <p class="text-muted small mb-0">No delivery agencies available. You can arrange your own pickup.</p>
                        <input type="hidden" name="delivery_agency_id" value="">
                    @else
                        <div class="d-flex flex-column gap-2">
                            <label class="d-flex align-items-center gap-3 p-3 delivery-opt checkout-opt {{ old('delivery_agency_id')==='' ? 'selected' : '' }}"
                                   style="cursor:pointer" data-fee="0">
                                <input type="radio" name="delivery_agency_id" value="" class="agency-radio"
                                       {{ old('delivery_agency_id')==='' ? 'checked' : '' }}>
                                <div>
                                    <div class="checkout-opt-name">Self pickup / arrange own delivery</div>
                                    <div class="checkout-opt-meta">No delivery fee</div>
                                </div>
                                <div class="ms-auto fw-bold">Free</div>
                            </label>
                            @foreach($agencies as $ag)
                            <label class="d-flex align-items-center gap-3 p-3 delivery-opt checkout-opt {{ old('delivery_agency_id')==$ag->id ? 'selected' : '' }}"
                                   style="cursor:pointer" data-fee="{{ $ag->base_rate }}">
                                <input type="radio" name="delivery_agency_id" value="{{ $ag->id }}" class="agency-radio"
                                       {{ old('delivery_agency_id')==$ag->id ? 'checked' : '' }}>
                                <div class="flex-fill">
                                    <div class="checkout-opt-name">{{ $ag->name }}</div>
                                    <div class="checkout-opt-meta">{{ $ag->deliveryEstimate() }} · {{ $ag->coverage_areas }}</div>
                                </div>
                                <div class="fw-bold" style="color: var(--mp-link)">
                                    {{ $ag->currency }} {{ number_format($ag->base_rate,2) }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Payment method --}}
                <div class="checkout-panel">
                    <h2><i class="fas fa-credit-card" aria-hidden="true"></i>Payment</h2>
                    @php
                        $payMethods = [
                            'cod'           => ['label' => 'Cash on Delivery', 'icon' => 'fa-money-bill-wave', 'color' => '#22c55e'],
                            'stripe'        => ['label' => 'Card (Stripe)', 'icon' => 'fa-credit-card', 'color' => '#635bff'],
                            'paystack'      => ['label' => 'Paystack', 'icon' => 'fa-credit-card', 'color' => '#00c3f7'],
                            'bank_transfer' => ['label' => 'Bank Transfer', 'icon' => 'fa-university', 'color' => '#0ea5e9'],
                            'mtn'           => ['label' => 'MTN Mobile Money', 'icon' => 'fa-mobile-alt', 'color' => '#fbbf24'],
                            'orange'        => ['label' => 'Orange Money', 'icon' => 'fa-mobile-alt', 'color' => '#f97316'],
                        ];
                        $stripeKey    = $settings['stripe_key']          ?? null;
                        $paystackKey  = $settings['paystack_public_key'] ?? null;
                        $bankEnabled  = ($settings['bank_transfer_enabled'] ?? '0') === '1';
                        $mtnEnabled   = ($settings['mtn_enabled']           ?? '0') === '1';
                        $orangeEnabled= ($settings['orange_enabled']        ?? '0') === '1';

                        $enabled = ['cod'];
                        if ($stripeKey)   $enabled[] = 'stripe';
                        if ($paystackKey) $enabled[] = 'paystack';
                        if ($bankEnabled) $enabled[] = 'bank_transfer';
                        if ($mtnEnabled)  $enabled[] = 'mtn';
                        if ($orangeEnabled) $enabled[] = 'orange';
                    @endphp
                    <div class="d-flex flex-column gap-2">
                        @foreach($enabled as $method)
                        @php $m = $payMethods[$method]; @endphp
                        <label class="d-flex align-items-center gap-3 p-3 checkout-opt"
                               style="cursor:pointer">
                            <input type="radio" name="payment_method" value="{{ $method }}"
                                   {{ (old('payment_method') ?: 'cod') === $method ? 'checked' : '' }}>
                            <i class="fas {{ $m['icon'] }}" style="color:{{ $m['color'] }};font-size:1.2rem;width:24px;text-align:center"></i>
                            <span class="fw-semibold" style="font-size:var(--text-sm)">{{ $m['label'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right: order summary --}}
            <div class="col-lg-5">
                <aside class="mk-panel checkout-summary" aria-labelledby="checkout-summary-title">
                    <div class="checkout-summary-head">
                        <h2 id="checkout-summary-title">Your order</h2>
                        <span class="checkout-summary-count">{{ collect($cart)->sum('qty') }} {{ \Illuminate\Support\Str::plural('item', collect($cart)->sum('qty')) }}</span>
                    </div>

                    @foreach($cart as $item)
                    @php $sub = $item['price'] * $item['qty']; @endphp
                    <div class="checkout-summary-item">
                        <div class="checkout-summary-thumb">
                            @if($item['image_url'])
                                <img src="{{ $item['image_url'] }}" alt="" loading="lazy">
                            @else
                                <i class="fas fa-box" aria-hidden="true"></i>
                            @endif
                        </div>
                        <span>
                            <span class="checkout-summary-item-name">{{ Str::limit($item['name'], 36) }}</span>
                            <span class="checkout-summary-item-meta">Qty {{ $item['qty'] }}</span>
                        </span>
                        <span class="checkout-summary-item-price">{{ $marketCurrency }} {{ number_format($sub,2) }}</span>
                    </div>
                    @endforeach

                    <hr class="checkout-summary-rule">
                    <div class="checkout-summary-row">
                        <span>Subtotal</span>
                        <span>{{ $marketCurrency }} {{ number_format($subtotal,2) }}</span>
                    </div>
                    {{-- No line when every item is from a marketplace seller: they pay a commission, not the customer. --}}
                    @if ($feeInfo['fee_amount'] > 0)
                        @php
                            $feeRate = trim(($feeInfo['percent_fee'] > 0 ? rtrim(rtrim(number_format($feeInfo['percent_fee'], 2), '0'), '.') . '%' : '')
                                . ($feeInfo['flat_fee'] > 0 ? ' + ' . number_format($feeInfo['flat_fee'], 2) : ''), ' +');
                        @endphp
                        <div class="checkout-summary-row">
                            <span>{{ $feeInfo['label'] }}@if ($feeRate) ({{ $feeRate }})@endif</span>
                            <span>{{ $marketCurrency }} {{ number_format($feeInfo['fee_amount'],2) }}</span>
                        </div>
                    @endif
                    <div class="checkout-summary-row">
                        <span>Delivery</span>
                        <span id="deliverySummary">—</span>
                    </div>
                    <div class="checkout-summary-row checkout-summary-total">
                        <span>Total</span>
                        <span id="totalSummary" style="color: var(--mp-link)">{{ $marketCurrency }} {{ number_format($subtotal + $feeInfo['fee_amount'],2) }}</span>
                    </div>

                    <button type="submit" class="btn-mp checkout-place-order">
                        <i class="fas fa-lock me-2"></i>Place Order
                    </button>
                    <p class="checkout-security"><i class="fas fa-lock me-1" aria-hidden="true"></i>Your information is protected.</p>
                    <a href="{{ route('marketplace.cart') }}" class="checkout-back"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Back to cart</a>
                </aside>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const subtotal  = {{ $subtotal }};
const platFee   = {{ $feeInfo['fee_amount'] }};
const marketCurrency = @json($marketCurrency);
const delivEl   = document.getElementById('deliverySummary');
const totalEl   = document.getElementById('totalSummary');

function updateTotals() {
    const chosen = document.querySelector('.agency-radio:checked');
    const delivFee = chosen ? parseFloat(chosen.closest('.delivery-opt')?.dataset.fee || 0) : 0;
    delivEl.textContent = delivFee > 0 ? delivFee.toFixed(2) : 'Free';
    totalEl.textContent = marketCurrency + ' ' + (subtotal + platFee + delivFee).toFixed(2);
}

function syncOptionSelection() {
    document.querySelectorAll('.checkout-opt').forEach(option => {
        option.classList.toggle('selected', Boolean(option.querySelector('input:checked')));
    });
}

document.querySelectorAll('.checkout-opt input[type="radio"]').forEach(input => {
    input.addEventListener('change', syncOptionSelection);
});
syncOptionSelection();

document.querySelectorAll('.agency-radio').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.delivery-opt').forEach(o => o.style.borderColor = 'var(--mp-border)');
        r.closest('.delivery-opt').style.borderColor = 'var(--mp-primary)';
        updateTotals();
    });
});

// Highlight selected on load
document.querySelectorAll('.agency-radio:checked').forEach(r => {
    r.closest('.delivery-opt').style.borderColor = 'var(--mp-primary)';
});
updateTotals();
</script>
@endpush
