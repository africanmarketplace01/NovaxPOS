{{-- Monthly / yearly switch for a [data-billing] plan grid (site.js). --}}
<div class="billing-toggle" role="group" aria-label="Billing period">
    <button type="button" data-billing-set="monthly" aria-pressed="true">Monthly</button>
    <button type="button" data-billing-set="yearly" aria-pressed="false">
        Yearly @if (! empty($yearlySavingsLabel))<span class="save-chip">{{ $yearlySavingsLabel }}</span>@endif
    </button>
</div>
