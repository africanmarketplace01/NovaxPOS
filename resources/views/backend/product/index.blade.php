@extends('backend.layout.main')

{{--
================================================================
 Product Index (Refactored)
================================================================
 HTML Partials  →  resources/views/backend/product/partials/
 JS File        →  public/js/product/product.main.js
 PHP→JS Bridge  →  product/partials/scripts/config.blade.php
================================================================
--}}

{{-- ── Inline styles ──────────────────────────────────────────────── --}}
@push('css')
@include('backend.product.partials.css')
@endpush

@section('content')

{{-- Flash messages --}}
<x-success-message key="create_message" />
<x-success-message key="import_message" />
<x-error-message   key="not_permitted" />
<x-errors-message  key="import_errors" />
<x-error-message   key="message" />

<section>
    <div class="container-fluid">

        {{-- ── Action buttons + filter toggle ─────────────────────── --}}
        @include('backend.product.partials.toolbar')

        {{-- ── Collapsible filter panel ────────────────────────────── --}}
        @include('backend.product.partials.filter-panel')

    </div>

    {{-- ── Product DataTable ───────────────────────────────────────── --}}
    @include('backend.product.partials.table')

</section>

{{-- ── Import product modal ────────────────────────────────────────── --}}
@include('backend.product.partials.modal-import')

{{-- ── Product details modal ──────────────────────────────────────── --}}
@include('backend.product.partials.modal-details')

@endsection

@push('scripts')

{{-- ── PHP → JS bridge (window.PRODUCT_CONFIG) ────────────────────── --}}
@include('backend.product.partials.scripts.config')

{{-- ── Product index core logic ───────────────────────────────────── --}}
<script src="{{ asset('js/product/product.main.js') }}"></script>

@endpush
