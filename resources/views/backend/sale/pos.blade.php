@extends('backend.layout.top-head')

{{--
================================================================
 POS — Main View (Refactored)
================================================================
 This file is an index that pulls everything in via @include
 and <script src>.  Do NOT put business logic here.

 HTML Partials  →  resources/views/backend/sale/pos/partials/
 JS Files       →  public/js/pos/
 PHP→JS Bridge  →  pos/partials/scripts/config.blade.php
================================================================
--}}

{{-- ── Inline styles ──────────────────────────────────────────────── --}}
@push('css')
@include('backend.sale.pos.partials.css')
@endpush


@section('content')

{{-- Page-level PHP variables used by partials --}}
@php
    $handle_discount_active = $role_has_permissions_list->where('name', 'handle_discount')->first();
    $price_type = session('price_type', '');
@endphp

{{-- Flash messages --}}
<x-success-message key="message" />
<x-error-message key="phone_number" />
<x-error-message key="not_permitted" />
<x-error-message key="error" />

{{-- ── Product panel (left column) ─────────────────────────────────── --}}
@include('backend.sale.pos.partials.product-panel')

{{-- ── Cart panel (right column) ───────────────────────────────────── --}}
@include('backend.sale.pos.partials.cart-panel')

{{-- ── Payment modal (#add-payment) ───────────────────────────────── --}}
@include('backend.sale.pos.partials.modal-payment')

{{-- ── Order totals modals (discount / coupon / shipping / invoice) ── --}}
@include('backend.sale.pos.partials.modal-totals')

{{-- ── Edit product modal + add customer modal ─────────────────────── --}}
@include('backend.sale.pos.partials.modal-edit-product')

{{-- ── Sale details & recent transactions modals ───────────────────── --}}
@include('backend.sale.pos.partials.modal-sale-details')

{{-- ── Expense modal ────────────────────────────────────────────────── --}}
@include('backend.sale.pos.partials.modal-expense')

{{-- ── Supplier payment modal ──────────────────────────────────────── --}}
@include('backend.sale.pos.partials.modal-supplier-payment')

{{-- ── Cash register modals (shown only when setting is active) ─────── --}}
@include('backend.sale.pos.partials.modal-cash-register')

{{-- ── Installment plan modal + QR scanner overlay ─────────────────── --}}
@include('backend.sale.pos.partials.modal-installment')

{{-- Placeholder div for print-invoice content injection ──────────── --}}
<div id="print-layout"></div>

@endsection


@push('scripts')

{{-- ── External libraries ───────────────────────────────────────────── --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script>
    const input = document.querySelector("#wa_number");
    window.intlTelInput(input, {
        initialCountry: "auto",
        geoIpLookup: function (callback) {
            fetch("https://ipapi.co/json")
                .then((res) => res.json())
                .then((data) => callback(data.country_code))
                .catch(() => callback("us"));
        },
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/mousetrap@1.6.5/mousetrap.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>

@if(in_array("razorpay", $options))
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
@endif

<audio id="audio" src="{{ url('beep/beep-07.mp3') }}" autoplay="false"></audio>

{{-- ── Phone / WhatsApp intlTelInput init + installment plan script ── --}}
@include('backend.sale.pos.partials.scripts.phone-init')

{{-- ── QR / Barcode scanner init ────────────────────────────────────── --}}
@include('backend.sale.pos.partials.scripts.qr-scanner')

{{-- ── PHP → JS bridge (window.POS_CONFIG) ────────────────────────── --}}
@include('backend.sale.pos.partials.scripts.config')

{{-- ── Cache-busting helper: version = file's last-modified time, so any --}}
{{-- JS update changes the URL and defeats the 1-year browser cache. ──── --}}
@php
    $posAsset = function ($path) {
        $full = public_path($path);
        $ver  = is_file($full) ? filemtime($full) : config('app.version', '1');
        return asset($path) . '?v=' . $ver;
    };
@endphp

{{-- ── POS core logic ───────────────────────────────────────────────── --}}
<script src="{{ $posAsset('js/pos/pos.main.js') }}"></script>

{{-- ── Customer display + utility scripts ──────────────────────────── --}}
<script src="{{ $posAsset('js/pos/pos.display.js') }}"></script>

{{-- ── Keyboard shortcuts ───────────────────────────────────────────── --}}
<script src="{{ $posAsset('js/pos/pos.shortcuts.js') }}"></script>

{{-- ── Category / brand live-filter ───────────────────────────────── --}}
<script src="{{ $posAsset('js/pos/pos.category-search.js') }}"></script>

@endpush
