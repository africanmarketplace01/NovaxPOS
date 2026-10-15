@extends('landlord.marketplace.layout')
@section('title', 'Complete Payment')

@section('content')
<div class="container" style="max-width:600px;padding:3rem 1rem">
    <div style="text-align:center;margin-bottom:2rem">
        <div style="width:64px;height:64px;background:var(--mkt-primary,#0066CC);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
            <i class="fas fa-credit-card" style="color:#fff;font-size:1.5rem"></i>
        </div>
        <h2 style="font-weight:800;margin-bottom:.25rem">Complete Your Payment</h2>
        <p class="text-muted" style="font-size:var(--text-sm)">Order <strong>{{ $order->order_number }}</strong> — Total: <strong>{{ $order->currency }} {{ number_format($order->total, 2) }}</strong></p>
    </div>

    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:2rem">

        @if($method === 'stripe')
        {{-- ── Stripe ─────────────────────────────────────────────────────── --}}
        <h5 class="fw-700 mb-3"><i class="fab fa-stripe" style="color:#635bff"></i> Pay with Card</h5>
        <div id="stripe-error" class="alert alert-danger d-none"></div>
        <div id="card-element" style="border:1px solid #e2e8f0;padding:1rem;border-radius:8px;margin-bottom:1rem"></div>
        <button id="stripe-pay-btn" class="btn w-100" style="background:#635bff;color:#fff;border-radius:8px;padding:.75rem;font-weight:700">
            <span id="stripe-btn-text"><i class="fas fa-lock me-2"></i>Pay {{ $order->currency }} {{ number_format($order->total,2) }}</span>
            <span id="stripe-spinner" class="d-none"><i class="fas fa-spinner fa-spin me-2"></i>Processing…</span>
        </button>
        <form id="stripe-confirm-form" action="{{ route('marketplace.payment.callback') }}" method="GET" class="d-none">
            <input type="hidden" name="payment_intent" id="stripe-pi-input">
            <input type="hidden" name="method" value="stripe">
        </form>

        @elseif($method === 'paystack')
        {{-- ── Paystack ────────────────────────────────────────────────────── --}}
        <h5 class="fw-700 mb-3"><i class="fas fa-credit-card" style="color:#00c3f7"></i> Pay with Paystack</h5>
        <p class="text-muted small mb-3">You will be redirected to Paystack's secure checkout to complete your payment.</p>
        <button onclick="launchPaystack()" class="btn w-100" style="background:#00c3f7;color:#fff;border-radius:8px;padding:.75rem;font-weight:700">
            <i class="fas fa-external-link-alt me-2"></i>Open Paystack Checkout
        </button>

        @elseif($method === 'bank_transfer')
        {{-- ── Bank Transfer ───────────────────────────────────────────────── --}}
        <h5 class="fw-700 mb-3"><i class="fas fa-university" style="color:#0ea5e9"></i> Bank Transfer</h5>
        @php $bankInfo = $settings['bank_transfer_details'] ?? 'Contact us for bank details.'; @endphp
        <div style="background:#f0f9ff;border-radius:8px;padding:1.25rem;margin-bottom:1rem;white-space:pre-wrap;font-size:var(--text-sm)">{{ $bankInfo }}</div>
        <div style="background:#fef9c3;border-radius:8px;padding:1rem;font-size:var(--text-sm);color:#854d0e">
            <i class="fas fa-info-circle me-1"></i>
            Use order <strong>{{ $order->order_number }}</strong> as your payment reference. Your order will be confirmed once payment is verified (typically within 24 hours).
        </div>
        <a href="{{ route('marketplace.order.success', $order->order_number) }}" class="btn w-100 mt-3" style="background:var(--mkt-primary,#0066CC);color:#fff;border-radius:8px;padding:.75rem;font-weight:700">
            I've Made the Transfer
        </a>

        @elseif(in_array($method, ['mtn','orange']))
        {{-- ── Mobile Money ────────────────────────────────────────────────── --}}
        @php
            $mmLabel  = $method === 'mtn' ? 'MTN Mobile Money' : 'Orange Money';
            $mmColor  = $method === 'mtn' ? '#fbbf24' : '#f97316';
            $mmDetail = $settings[$method . '_mm_details'] ?? 'Contact us for Mobile Money details.';
        @endphp
        <h5 class="fw-700 mb-3"><i class="fas fa-mobile-alt" style="color:{{ $mmColor }}"></i> {{ $mmLabel }}</h5>
        <div style="background:#fff7ed;border-radius:8px;padding:1.25rem;margin-bottom:1rem;white-space:pre-wrap;font-size:var(--text-sm)">{{ $mmDetail }}</div>
        <div style="background:#fef9c3;border-radius:8px;padding:1rem;font-size:var(--text-sm);color:#854d0e">
            <i class="fas fa-info-circle me-1"></i>
            Use order <strong>{{ $order->order_number }}</strong> as reference. Confirmation within 1–2 hours.
        </div>
        <a href="{{ route('marketplace.order.success', $order->order_number) }}" class="btn w-100 mt-3" style="background:{{ $mmColor }};color:#fff;border-radius:8px;padding:.75rem;font-weight:700">
            I've Sent the Payment
        </a>

        @else
        {{-- ── Fallback ─────────────────────────────────────────────────────── --}}
        <p class="text-muted">Payment method not recognized. Please contact support with order <strong>{{ $order->order_number }}</strong>.</p>
        @endif

    </div>

    <div style="text-align:center;margin-top:1.5rem">
        <a href="{{ route('marketplace.index') }}" style="color:#94a3b8;font-size:var(--text-xs)">← Return to Marketplace</a>
    </div>
</div>
@endsection

@push('scripts')
@if($method === 'stripe' && !empty($settings['stripe_key']))
<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe  = Stripe('{{ $settings['stripe_key'] }}');
const elements = stripe.elements();
const card     = elements.create('card');
card.mount('#card-element');

document.getElementById('stripe-pay-btn').addEventListener('click', async () => {
    document.getElementById('stripe-btn-text').classList.add('d-none');
    document.getElementById('stripe-spinner').classList.remove('d-none');
    document.getElementById('stripe-pay-btn').disabled = true;

    const { paymentMethod, error } = await stripe.createPaymentMethod({ type: 'card', card });
    if (error) {
        document.getElementById('stripe-error').textContent = error.message;
        document.getElementById('stripe-error').classList.remove('d-none');
        document.getElementById('stripe-btn-text').classList.remove('d-none');
        document.getElementById('stripe-spinner').classList.add('d-none');
        document.getElementById('stripe-pay-btn').disabled = false;
        return;
    }
    document.getElementById('stripe-pi-input').value = paymentMethod.id;
    document.getElementById('stripe-confirm-form').submit();
});
</script>
@endif

@if($method === 'paystack' && !empty($settings['paystack_key']))
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
function launchPaystack() {
    var handler = PaystackPop.setup({
        key: '{{ $settings['paystack_key'] }}',
        email: '{{ $order->customer_email ?: 'customer@marketplace.com' }}',
        amount: {{ (int)($order->total * 100) }},
        currency: '{{ $order->currency }}',
        ref: '{{ $order->order_number }}',
        onClose: function() {},
        callback: function(response) {
            window.location = '{{ route('marketplace.payment.callback') }}?reference=' + response.reference + '&method=paystack';
        }
    });
    handler.openIframe();
}
</script>
@endif
@endpush
