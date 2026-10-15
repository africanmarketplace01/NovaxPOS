@extends('backend.layout.main')

{{--
================================================================
 Purchase Index (Refactored)
================================================================
 HTML Partials  →  resources/views/backend/purchase/partials/
 JS File        →  public/js/purchase/purchase.main.js
 PHP→JS Bridge  →  purchase/partials/scripts/config.blade.php
================================================================
--}}

{{-- ── Inline styles ──────────────────────────────────────────────── --}}
@push('css')
@include('backend.purchase.partials.css')
@endpush

@section('content')

{{-- Flash messages --}}
<x-success-message key="message" />
<x-error-message   key="not_permitted" />

<section>
    <div class="container-fluid">

        {{-- ── Action buttons + filter toggle ─────────────────────── --}}
        @include('backend.purchase.partials.toolbar')

        {{-- ── Collapsible filter panel ────────────────────────────── --}}
        @include('backend.purchase.partials.filter-panel')

    </div>

    {{-- ── Purchase DataTable ─────────────────────────────────────── --}}
    @include('backend.purchase.partials.table')

</section>

{{-- ── Purchase details modal ─────────────────────────────────────── --}}
@include('backend.purchase.partials.modal-details')

{{-- ── Payment modals (view / add / edit) ─────────────────────────── --}}
@include('backend.purchase.partials.modal-payment')

@endsection

@push('scripts')

{{-- ── PHP → JS bridge (window.PURCHASE_CONFIG) ───────────────────── --}}
@include('backend.purchase.partials.scripts.config')

{{-- ── Purchase index core logic ──────────────────────────────────── --}}
<script src="{{ asset('js/purchase/purchase.main.js') }}"></script>
<script src="https://js.stripe.com/v3/"></script>

@endpush
