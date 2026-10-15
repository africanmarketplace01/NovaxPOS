@extends('landlord.layouts.public')

@section('title', 'Businesses — ' . $siteN)
@section('description', 'Businesses that run on ' . $siteN . '. Browse their stores on the marketplace.')

@section('content')
    @include('landlord.partials.page-hero', [
        'eyebrow' => 'Businesses',
        'title' => 'Businesses powered by ' . $siteN,
        'lead' => 'From retail shops to pharmacies and restaurants: discover the businesses running on the platform and shop their stores on the marketplace.',
        'center' => true,
        'extraView' => 'landlord.partials.search-box',
        'extraData' => ['action' => route('businesses'), 'placeholder' => 'Search businesses…', 'label' => 'Search businesses'],
    ])

    <section class="section">
        <div class="container">
            @if ($businesses->isEmpty())
                <div class="empty">
                    <i class="fas fa-store-slash" aria-hidden="true"></i>
                    <p>No businesses found{{ request('search') ? ' for “' . request('search') . '”' : '' }}.</p>
                    @if (request('search'))
                        <a href="{{ route('businesses') }}" class="link-arrow" style="margin-top:1rem">View all businesses <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </div>
            @else
                <div class="grid grid--4">
                    @foreach ($businesses as $tenant)
                        @php $hasStore = (bool) $tenant->ecommerce_enabled; @endphp
                        <a href="{{ $hasStore ? route('marketplace.shop', $tenant->id) : route('marketplace.index') }}" class="card card--hover biz-card reveal">
                            @if ($tenant->logo_url)
                                <span class="biz-card__logo"><img src="{{ $tenant->logo_url }}" alt="" loading="lazy"></span>
                            @else
                                <span class="biz-card__initials" aria-hidden="true">{{ $tenant->initials }}</span>
                            @endif
                            <span class="biz-card__name">{{ $tenant->company_name }}</span>
                            <span class="biz-card__cta">{{ $hasStore ? 'Visit store' : 'Explore marketplace' }} <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                        </a>
                    @endforeach
                </div>
                {{ $businesses->links('landlord.partials.pagination') }}
            @endif
        </div>
    </section>

    <section class="section cta-band">
        <div class="container">
            <h2>Want your business listed here?</h2>
            <p>Open a {{ $siteN }} store and your products can appear on the marketplace in one click.</p>
            <div class="cta-band__actions">
                <a href="{{ route('register') }}" class="btn btn--accent btn--lg"><i class="fas fa-rocket" aria-hidden="true"></i> Open your store</a>
            </div>
        </div>
    </section>
@endsection
