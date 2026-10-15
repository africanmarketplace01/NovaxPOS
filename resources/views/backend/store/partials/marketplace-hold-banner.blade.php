{{-- Warns a store whose marketplace listings are hidden because a fee invoice is overdue. --}}
@php
    try {
        $holdTenant = \App\Models\landlord\Tenant::find(tenancy()->tenant?->id ?? config('app.tenant_id'));
    } catch (\Throwable $e) {
        $holdTenant = null;
    }
@endphp
@if ($holdTenant && $holdTenant->marketplace_hold)
    <div class="alert alert-warning d-flex" role="alert" style="gap:.75rem;">
        <i class="fas fa-eye-slash" style="margin-top:3px;"></i>
        <div>
            <strong>Your products are hidden from the marketplace.</strong>
            {{ $holdTenant->marketplace_hold_reason }}. Pay the invoice marked <em>Payable</em> on
            <a href="{{ route('store.platform-fees') }}">Platform Fees</a> and your listings come back as soon as the
            payment is confirmed. Your store, products and orders are unaffected.
        </div>
    </div>
@endif
