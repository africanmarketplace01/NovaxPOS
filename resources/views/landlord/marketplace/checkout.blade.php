@extends('landlord.marketplace.layout')
@section('title', 'Checkout')

@section('content')
<div class="mp-breadcrumb">
    <div class="container">
        <a href="{{ route('marketplace.index') }}">Home</a> /
        <a href="{{ route('marketplace.cart') }}">Cart</a> / Checkout
    </div>
</div>

<div class="container py-4" style="max-width:950px">
    <h1 class="fw-bold mb-4" style="font-size:1.6rem">Checkout</h1>

    @if($errors->any())
    <div class="flash-error">
        <ul class="mb-0" style="padding-left:1.25rem">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('marketplace.order.place') }}" method="POST" id="checkoutForm">
        @csrf
        <div class="row g-4">
            {{-- Left column --}}
            <div class="col-lg-7">
                {{-- Sign-in prompt for guests --}}
                @guest('customer')
                <div class="p-3 mb-3 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
                     style="background:#eff6ff;border:1px solid #bfdbfe">
                    <span style="font-size:var(--text-sm);color:#1e40af">
                        <i class="fas fa-user-circle me-1"></i> Have an account? Sign in for faster checkout &amp; order tracking.
                    </span>
                    <a href="{{ route('customer.login') }}" class="btn btn-sm btn-primary">Sign in</a>
                </div>
                @endguest

                {{-- Customer details --}}
                <div class="p-4 bg-white rounded-3 mb-3" style="border:1px solid var(--mp-border)">
                    <h5 class="fw-bold mb-3"><i class="fas fa-user me-2" style="color: var(--mp-link)"></i>Your Details
                        @auth('customer')<span class="badge bg-success ms-2" style="font-weight:600"><i class="fas fa-check fa-xs"></i> Signed in</span>@endauth
                    </h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Full Name *</label>
                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name', $customer->name ?? '') }}" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Email</label>
                            <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $customer->email ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Phone *</label>
                            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone', $customer->phone ?? '') }}" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Delivery Address *</label>
                        <textarea name="customer_address" class="form-control" rows="3" required placeholder="Street, city, country…">{{ old('customer_address', $customer->address ?? '') }}</textarea>
                    </div>

                    {{-- Create account (guests only) --}}
                    @guest('customer')
                    <div class="mt-3 p-3 rounded-3" style="background:#f8fafc;border:1px dashed var(--mp-border)">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="create_account" value="1" id="createAccount"
                                   onchange="document.getElementById('acctPwdWrap').style.display=this.checked?'block':'none'">
                            <label class="form-check-label fw-semibold" for="createAccount" style="font-size:var(--text-sm)">
                                Create an account for faster checkout &amp; to track this order
                            </label>
                        </div>
                        <div id="acctPwdWrap" style="display:none" class="mt-2">
                            <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Choose a password</label>
                            <input type="password" name="account_password" class="form-control" minlength="6" placeholder="At least 6 characters">
                            <div class="form-text" style="font-size:var(--text-xs)">We'll use your email &amp; phone above for your account.</div>
                        </div>
                    </div>
                    @endguest
                    <div class="mt-3">
                        <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Order Note</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Special instructions…">{{ old('note') }}</textarea>
                    </div>
                </div>

                {{-- Delivery agency --}}
                <div class="p-4 bg-white rounded-3 mb-3" style="border:1px solid var(--mp-border)">
                    <h5 class="fw-bold mb-3"><i class="fas fa-truck me-2" style="color: var(--mp-link)"></i>Delivery</h5>
                    @if($agencies->isEmpty())
                        <p class="text-muted small mb-0">No delivery agencies available. You can arrange your own pickup.</p>
                        <input type="hidden" name="delivery_agency_id" value="">
                    @else
                        <div class="d-flex flex-column gap-2">
                            <label class="d-flex align-items-center gap-3 p-3 rounded-3 delivery-opt {{ old('delivery_agency_id')==='' ? 'selected' : '' }}"
                                   style="border:2px solid var(--mp-border);cursor:pointer" data-fee="0">
                                <input type="radio" name="delivery_agency_id" value="" class="agency-radio"
                                       {{ old('delivery_agency_id')==='' ? 'checked' : '' }}>
                                <div>
                                    <div class="fw-semibold">Self Pickup / Arrange Own Delivery</div>
                                    <div class="small text-muted">No delivery fee</div>
                                </div>
                                <div class="ms-auto fw-bold">Free</div>
                            </label>
                            @foreach($agencies as $ag)
                            <label class="d-flex align-items-center gap-3 p-3 rounded-3 delivery-opt {{ old('delivery_agency_id')==$ag->id ? 'selected' : '' }}"
                                   style="border:2px solid var(--mp-border);cursor:pointer" data-fee="{{ $ag->base_rate }}">
                                <input type="radio" name="delivery_agency_id" value="{{ $ag->id }}" class="agency-radio"
                                       {{ old('delivery_agency_id')==$ag->id ? 'checked' : '' }}>
                                <div class="flex-fill">
                                    <div class="fw-semibold">{{ $ag->name }}</div>
                                    <div class="small text-muted">{{ $ag->deliveryEstimate() }} · {{ $ag->coverage_areas }}</div>
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
                <div class="p-4 bg-white rounded-3" style="border:1px solid var(--mp-border)">
                    <h5 class="fw-bold mb-3"><i class="fas fa-credit-card me-2" style="color: var(--mp-link)"></i>Payment</h5>
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
                        <label class="d-flex align-items-center gap-3 p-3 rounded-3"
                               style="border:2px solid var(--mp-border);cursor:pointer">
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
                <div class="p-4 bg-white rounded-3" style="border:1px solid var(--mp-border);position:sticky;top:80px">
                    <h5 class="fw-bold mb-3">Order Summary</h5>

                    @foreach($cart as $item)
                    @php $sub = $item['price'] * $item['qty']; @endphp
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ Str::limit($item['name'],28) }} ×{{ $item['qty'] }}</span>
                        <span class="fw-semibold">{{ number_format($sub,2) }}</span>
                    </div>
                    @endforeach

                    <hr>
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Subtotal</span>
                        <span>{{ number_format($subtotal,2) }}</span>
                    </div>
                    {{-- No line when every item is from a marketplace seller: they pay a commission, not the customer. --}}
                    @if ($feeInfo['fee_amount'] > 0)
                        @php
                            $feeRate = trim(($feeInfo['percent_fee'] > 0 ? rtrim(rtrim(number_format($feeInfo['percent_fee'], 2), '0'), '.') . '%' : '')
                                . ($feeInfo['flat_fee'] > 0 ? ' + ' . number_format($feeInfo['flat_fee'], 2) : ''), ' +');
                        @endphp
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $feeInfo['label'] }}@if ($feeRate) ({{ $feeRate }})@endif</span>
                            <span>{{ number_format($feeInfo['fee_amount'],2) }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Delivery</span>
                        <span id="deliverySummary">—</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold mb-4" style="font-size:var(--text-lg)">
                        <span>Total</span>
                        <span id="totalSummary" style="color: var(--mp-link)">{{ number_format($subtotal + $feeInfo['fee_amount'],2) }}</span>
                    </div>

                    <button type="submit" class="btn-mp w-100" style="padding:.75rem;font-size:var(--text-base)">
                        <i class="fas fa-lock me-2"></i>Place Order
                    </button>
                    <a href="{{ route('marketplace.cart') }}" class="btn-mp-outline d-block text-center mt-2" style="padding:.6rem">
                        ← Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const subtotal  = {{ $subtotal }};
const platFee   = {{ $feeInfo['fee_amount'] }};
const delivEl   = document.getElementById('deliverySummary');
const totalEl   = document.getElementById('totalSummary');

function updateTotals() {
    const chosen = document.querySelector('.agency-radio:checked');
    const delivFee = chosen ? parseFloat(chosen.closest('.delivery-opt')?.dataset.fee || 0) : 0;
    delivEl.textContent = delivFee > 0 ? delivFee.toFixed(2) : 'Free';
    totalEl.textContent = (subtotal + platFee + delivFee).toFixed(2);
}

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
