{{--
    "Just want to sell online?" banner pointing at the free marketplace-seller
    signup. Renders nothing until the Marketplace Seller plan exists.
--}}
@php
    $sellerPlan = \App\Models\landlord\Package::marketplaceSeller();
    if ($sellerPlan) {
        [$sellerPercent, $sellerFlat] = \App\Services\Marketplace\FeeSplit::sellerRate('');
        $sellerRate = collect([
            $sellerPercent > 0 ? rtrim(rtrim(number_format($sellerPercent, 2), '0'), '.') . '%' : null,
            $sellerFlat > 0 ? '$' . number_format($sellerFlat, 2) : null,
        ])->filter()->implode(' + ') ?: 'No commission';
    }
@endphp
@if ($sellerPlan)
    <div class="banner reveal" style="margin-top:2.5rem">
        <div>
            <p class="eyebrow" style="color:var(--np-accent)">Just want to sell online?</p>
            <h3 style="margin-top:.4rem">Sell on the marketplace for free</h3>
            <p>No subscription. List your products, take orders and pay only {{ $sellerRate }} per order when you make a sale. Upgrade to the full POS whenever you&rsquo;re ready.</p>
        </div>
        <div class="banner__actions">
            <a href="{{ route('sell') }}" class="btn btn--accent"><i class="fas fa-store" aria-hidden="true"></i> Start selling free</a>
        </div>
    </div>
@endif
