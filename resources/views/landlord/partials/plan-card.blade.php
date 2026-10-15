{{--
    One subscription plan, used by the landing page and the pricing page so
    both always show the same prices.

    $plan          App\Models\landlord\Package
    $promotion     current App\Models\landlord\PlanPromotion or null; its
                   discount applies to every plan while it is live
    $featureLimit  optional max number of feature lines (null = all)

    Monthly / yearly content is marked .only-monthly / .only-yearly and is
    switched by the [data-billing] container around the cards (site.js).
--}}
@php
    $promotion = $promotion ?? null;
    $mBase = (float) $plan->price_monthly;
    $yBase = (float) $plan->price_yearly;
    $oBase = (float) $plan->price_onetime;
    $mNew = $promotion ? $promotion->applyTo($mBase) : $mBase;
    $yNew = $promotion ? $promotion->applyTo($yBase) : $yBase;
    $mOff = $promotion && $mNew < $mBase;
    $yOff = $promotion && $yNew < $yBase;
    $isFree = $mBase == 0 && $plan->is_free_trial;
    $isRecurring = $mBase > 0 || $yBase > 0;
    $lifetimeOnly = ! $isRecurring && ! $isFree && $oBase > 0;
    $saving = $plan->getYearlySavingPercent();
    $features = array_values((array) $plan->features);
    if (isset($featureLimit)) {
        $features = array_slice($features, 0, $featureLimit);
    }
    $ctaClass = $plan->badge ? 'btn btn--primary btn--block' : 'btn btn--outline btn--block';
@endphp

<article @class(['card plan reveal', 'plan--featured' => $plan->badge])>
    @if ($plan->badge)
        <span class="plan__badge">{{ $plan->badge }}</span>
    @endif

    <h3 class="plan__name">{{ $plan->name }}</h3>
    @if ($plan->description)
        <p class="plan__desc">{{ $plan->description }}</p>
    @endif

    <div class="plan__price">
        @if ($isFree)
            <p><span class="plan__amount">Free</span></p>
        @elseif ($lifetimeOnly)
            <p><span class="plan__amount">${{ number_format($oBase, 0) }}</span> <span class="plan__period">once</span></p>
            <p class="plan__note">Pay once, use forever.</p>
        @else
            {{-- Monthly view --}}
            <div class="only-monthly">
                <p>
                    @if ($mOff)<span class="plan__old">${{ number_format($mBase, 0) }}</span>@endif
                    <span class="plan__amount">${{ number_format($mNew, 0) }}</span><span class="plan__period">/mo</span>
                </p>
                @if ($mOff)<span class="plan__chip plan__chip--promo">{{ $promotion->label() }}</span>@endif
            </div>
            {{-- Yearly view: shown as a monthly equivalent, billed yearly --}}
            <div class="only-yearly">
                @if ($yBase > 0)
                    <p>
                        @if ($yOff)<span class="plan__old">${{ number_format($yBase / 12, 0) }}</span>@endif
                        <span class="plan__amount">${{ number_format($yNew / 12, 0) }}</span><span class="plan__period">/mo</span>
                    </p>
                    <p class="plan__note">Billed ${{ number_format($yNew, 0) }} per year</p>
                    @if ($yOff)
                        <span class="plan__chip plan__chip--promo">{{ $promotion->label() }}</span>
                    @elseif ($saving > 0)
                        <span class="plan__chip">Save {{ $saving }}%</span>
                    @endif
                @else
                    <p>
                        @if ($mOff)<span class="plan__old">${{ number_format($mBase, 0) }}</span>@endif
                        <span class="plan__amount">${{ number_format($mNew, 0) }}</span><span class="plan__period">/mo</span>
                    </p>
                    <p class="plan__note">Monthly billing only</p>
                @endif
            </div>
        @endif
    </div>

    @if ($plan->is_free_trial && $plan->trial_days)
        <p class="plan__trial"><i class="fas fa-gift" aria-hidden="true"></i> {{ $plan->trial_days }}-day free trial included</p>
    @elseif ($plan->allow_paid_trial)
        <p class="plan__trial"><i class="fas fa-clock" aria-hidden="true"></i> Trial period available</p>
    @endif

    @if ($oBase > 0 && ! $lifetimeOnly)
        <p class="plan__onetime">or <b>${{ number_format($oBase, 0) }}</b> once &middot; lifetime access</p>
    @endif

    <ul class="plan__features">
        <li><i class="fas fa-check" aria-hidden="true"></i> {{ $plan->product_limit ? number_format($plan->product_limit) . ' products' : 'Unlimited products' }}</li>
        <li><i class="fas fa-check" aria-hidden="true"></i> {{ $plan->user_limit ? number_format($plan->user_limit) . ' staff users' : 'Unlimited staff users' }}</li>
        <li><i class="fas fa-check" aria-hidden="true"></i> {{ $plan->warehouse_limit ? number_format($plan->warehouse_limit) . ' warehouses' : 'Unlimited warehouses' }}</li>
        @foreach ($features as $feature)
            <li><i class="fas fa-check" aria-hidden="true"></i> {{ ucwords(str_replace(['_', '-'], ' ', $feature)) }}</li>
        @endforeach
    </ul>

    <div class="plan__cta">
        @if ($isFree)
            <a href="{{ route('register', ['package_id' => $plan->id, 'type' => 'free_trial']) }}" class="{{ $ctaClass }}">
                Start free trial <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        @elseif ($isRecurring)
            {{-- Without a yearly price the monthly button stays visible in yearly view. --}}
            <a href="{{ route('register', ['package_id' => $plan->id, 'type' => 'monthly']) }}"
               class="{{ $ctaClass }} @if ($yBase > 0) only-monthly @endif">
                Get started <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
            @if ($yBase > 0)
                <a href="{{ route('register', ['package_id' => $plan->id, 'type' => 'yearly']) }}" class="{{ $ctaClass }} only-yearly">
                    Get started yearly <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            @endif
        @endif
        @if ($oBase > 0)
            <a href="{{ route('register', ['package_id' => $plan->id, 'type' => 'one_time']) }}"
               class="btn btn--block {{ $lifetimeOnly ? 'btn--primary' : 'btn--outline' }}">
                <i class="fas fa-infinity" aria-hidden="true"></i> Buy lifetime access
            </a>
        @endif
    </div>
</article>
