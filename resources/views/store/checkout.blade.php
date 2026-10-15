@extends('store.layout')
@section('title', 'Checkout — ' . ($store->store_name ?? 'Store'))

@section('content')
<div class="store-breadcrumb">
    <div class="container">
        <a href="{{ route('store.index') }}">Home</a> /
        <a href="{{ route('store.cart') }}">Cart</a> /
        <span>Checkout</span>
    </div>
</div>

<div class="container" style="padding:2.5rem 0 4rem;max-width:900px">
    <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:1.5rem">Checkout</h1>

    @if($errors->any())
    <div class="flash-error">
        <ul class="mb-0" style="padding-left:1.25rem">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('store.order.place') }}" method="POST">
        @csrf
        <div class="row g-4">
            {{-- Customer details --}}
            <div class="col-lg-7">
                <div style="background:#fff;border-radius:14px;border:1px solid var(--store-border);padding:1.75rem">
                    <h5 style="font-weight:700;margin-bottom:1.25rem">
                        <i class="fas fa-user me-2" style="color:var(--store-primary)"></i>Your Details
                    </h5>
                    <div class="mb-3">
                        <label class="form-label fw-600" style="font-size:.875rem">Full Name *</label>
                        <input type="text" name="customer_name" class="form-control" value="{{ old('customer_name') }}" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:.875rem">Email</label>
                            <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600" style="font-size:.875rem">Phone *</label>
                            <input type="text" name="customer_phone" class="form-control" value="{{ old('customer_phone') }}" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-600" style="font-size:.875rem">Delivery Address</label>
                        <textarea name="customer_address" class="form-control" rows="3" placeholder="Street, city, zip…">{{ old('customer_address') }}</textarea>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-600" style="font-size:.875rem">Order Note (optional)</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Any special instructions…">{{ old('note') }}</textarea>
                    </div>
                    @if($store->order_note)
                    <div style="background:#f0f9ff;border-left:3px solid var(--store-primary);padding:.75rem 1rem;border-radius:0 8px 8px 0;font-size:.82rem;color:#0c4a6e;margin-top:1rem">
                        <i class="fas fa-info-circle me-1"></i> {{ $store->order_note }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- Order summary + delivery + payment --}}
            <div class="col-lg-5">
                <div style="background:#fff;border-radius:14px;border:1px solid var(--store-border);padding:1.75rem;position:sticky;top:80px">
                    <h5 style="font-weight:700;margin-bottom:1rem">Order Summary</h5>
                    @php $subtotal = 0; @endphp
                    @foreach($cart as $item)
                    @php $sub = $item['price'] * $item['qty']; $subtotal += $sub; @endphp
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.4rem 0;border-bottom:1px solid #f1f5f9">
                        <span>{{ Str::limit($item['name'], 28) }} × {{ $item['qty'] }}</span>
                        <span style="font-weight:600">{{ number_format($sub, 2) }}</span>
                    </div>
                    @endforeach

                    <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.4rem 0;color:#64748b">
                        <span>Subtotal</span><span>{{ number_format($subtotal, 2) }}</span>
                    </div>
                    @if(isset($store->tax_rate) && $store->tax_rate > 0)
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.4rem 0;color:#64748b">
                        <span>Tax ({{ $store->tax_rate }}%)</span>
                        <span>{{ number_format($subtotal * $store->tax_rate / 100, 2) }}</span>
                    </div>
                    @endif
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.4rem 0;color:#64748b" id="deliveryRow">
                        <span>Delivery</span><span id="deliveryAmt">—</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;padding-top:.75rem;margin-top:.5rem;border-top:2px solid #f1f5f9">
                        <span>Total</span>
                        <span style="color:var(--store-primary)" id="grandTotal">{{ number_format($subtotal, 2) }}</span>
                    </div>
                </div>

                {{-- Delivery selection --}}
                @if(isset($agencies) && $agencies->count() > 0)
                <div style="background:#fff;border-radius:14px;border:1px solid var(--store-border);padding:1.5rem;margin-top:1rem">
                    <h6 style="font-weight:700;margin-bottom:1rem"><i class="fas fa-truck me-2" style="color:var(--store-primary)"></i>Delivery</h6>
                    <div style="display:flex;flex-direction:column;gap:.5rem">
                        <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer" data-fee="0">
                            <input type="radio" name="delivery_agency_id" value="" class="agency-radio" checked>
                            <div style="flex:1"><div style="font-weight:600;font-size:.85rem">Self Pickup</div><div style="font-size:.75rem;color:#94a3b8">No delivery fee</div></div>
                            <span style="font-weight:700;color:#22c55e">Free</span>
                        </label>
                        @foreach($agencies as $ag)
                        <label style="display:flex;align-items:center;gap:.75rem;padding:.75rem;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer" data-fee="{{ $ag->base_rate }}">
                            <input type="radio" name="delivery_agency_id" value="{{ $ag->id }}" class="agency-radio">
                            <div style="flex:1">
                                <div style="font-weight:600;font-size:.85rem">{{ $ag->name }}</div>
                                <div style="font-size:.75rem;color:#94a3b8">{{ $ag->deliveryEstimate() }}</div>
                            </div>
                            <span style="font-weight:700;color:var(--store-primary)">{{ $ag->currency }} {{ number_format($ag->base_rate,2) }}</span>
                        </label>
                        @endforeach
                    </div>

                    {{-- Zone-to-zone selectors (shown when the chosen agency has zones) --}}
                    <div id="zonePicker" style="display:none;margin-top:.85rem;gap:.5rem;grid-template-columns:1fr 1fr;grid-gap:.5rem">
                        <div>
                            <label style="font-size:.7rem;color:#94a3b8">From zone</label>
                            <select name="from_zone_id" id="fromZone" class="form-control form-control-sm"></select>
                        </div>
                        <div>
                            <label style="font-size:.7rem;color:#94a3b8">To zone</label>
                            <select name="to_zone_id" id="toZone" class="form-control form-control-sm"></select>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Payment method --}}
                @if(isset($paymentMethods) && count($paymentMethods) > 0)
                <div style="background:#fff;border-radius:14px;border:1px solid var(--store-border);padding:1.5rem;margin-top:1rem">
                    <h6 style="font-weight:700;margin-bottom:1rem"><i class="fas fa-credit-card me-2" style="color:var(--store-primary)"></i>Payment</h6>
                    @php
                        $pmLabels=['cod'=>'Cash on Delivery','stripe'=>'Card (Stripe)','paystack'=>'Paystack','bank_transfer'=>'Bank Transfer','mtn'=>'MTN Mobile Money','orange'=>'Orange Money'];
                        $pmIcons=['cod'=>'fa-money-bill-wave','stripe'=>'fa-credit-card','paystack'=>'fa-credit-card','bank_transfer'=>'fa-university','mtn'=>'fa-mobile-alt','orange'=>'fa-mobile-alt'];
                    @endphp
                    @foreach($paymentMethods as $pm)
                    <label style="display:flex;align-items:center;gap:.75rem;padding:.65rem;border:2px solid #e2e8f0;border-radius:8px;cursor:pointer;margin-bottom:.5rem">
                        <input type="radio" name="payment_method" value="{{ $pm }}" {{ $pm==='cod' ? 'checked' : '' }}>
                        <i class="fas {{ $pmIcons[$pm] ?? 'fa-credit-card' }}" style="font-size:1rem;width:20px;text-align:center;color:var(--store-primary)"></i>
                        <span style="font-weight:600;font-size:.875rem">{{ $pmLabels[$pm] ?? $pm }}</span>
                    </label>
                    @endforeach
                </div>
                @else
                    <input type="hidden" name="payment_method" value="cod">
                @endif

                <button type="submit" class="btn btn-store-primary w-100 mt-3" style="font-size:.95rem;padding:.65rem">
                    <i class="fas fa-check-circle me-2"></i> Place Order
                </button>
                <a href="{{ route('store.cart') }}" class="btn btn-light w-100 mt-2" style="font-size:.875rem">
                    <i class="fas fa-arrow-left me-1"></i> Back to Cart
                </a>
            </div>
        </div>
    </form>
</div>

@section('scripts')
<script>
const subtotal = {{ collect(session('store_cart', []))->sum(fn($i) => $i['price'] * $i['qty']) }};
const taxRate  = {{ $store->tax_rate ?? 0 }} / 100;
const taxAmt   = subtotal * taxRate;

// Per-agency zones + zone-to-zone price matrix
const agencyData = {
@foreach($agencies as $ag)
    "{{ $ag->id }}": {
        base: {{ $ag->base_rate ?? 0 }},
        zones: [@foreach(($deliveryZones[$ag->id] ?? collect()) as $z){id:{{ $z->id }},name:@json($z->name)},@endforeach],
        prices: [@foreach(($deliveryPrices[$ag->id] ?? collect()) as $p){from:{{ $p->from_zone_id }},to:{{ $p->to_zone_id }},price:{{ $p->price }}},@endforeach]
    },
@endforeach
};

const zonePicker = document.getElementById('zonePicker');
const fromZone = document.getElementById('fromZone');
const toZone = document.getElementById('toZone');

function currentAgencyId() {
    const chosen = document.querySelector('.agency-radio:checked');
    return chosen ? chosen.value : '';
}

function populateZones() {
    const id = currentAgencyId();
    const data = agencyData[id];
    if (!data || !data.zones.length) { zonePicker.style.display = 'none'; return; }
    const opts = data.zones.map(z => `<option value="${z.id}">${z.name}</option>`).join('');
    fromZone.innerHTML = opts; toZone.innerHTML = opts;
    zonePicker.style.display = 'grid';
}

function currentDeliveryFee() {
    const id = currentAgencyId();
    if (!id) return 0;
    const data = agencyData[id];
    if (data && data.zones.length && zonePicker.style.display !== 'none') {
        const f = parseInt(fromZone.value), t = parseInt(toZone.value);
        const match = data.prices.find(p => p.from === f && p.to === t);
        if (match) return parseFloat(match.price);
    }
    const chosen = document.querySelector('.agency-radio:checked');
    return chosen ? parseFloat(chosen.closest('label')?.dataset.fee || 0) : 0;
}

function recalcTotal() {
    const delivFee = currentDeliveryFee();
    document.getElementById('deliveryAmt').textContent = delivFee > 0 ? delivFee.toFixed(2) : 'Free';
    document.getElementById('grandTotal').textContent = (subtotal + taxAmt + delivFee).toFixed(2);
}

document.querySelectorAll('.agency-radio').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.agency-radio').forEach(a => a.closest('label').style.borderColor='#e2e8f0');
        r.closest('label').style.borderColor = 'var(--store-primary)';
        populateZones();
        recalcTotal();
    });
});
[fromZone, toZone].forEach(s => s && s.addEventListener('change', recalcTotal));

document.querySelectorAll('.agency-radio:checked').forEach(r => r.closest('label').style.borderColor='var(--store-primary)');
populateZones();
recalcTotal();
</script>
@endsection
@endsection
