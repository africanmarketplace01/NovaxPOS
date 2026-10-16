@extends('landlord.layouts.public')

@section('title', 'Businesses — ' . $siteN)
@section('description', 'Businesses that run on ' . $siteN . '. Browse their stores on the marketplace.')

@section('content')
    <div class="businesses-page-hero">
        @include('landlord.partials.page-hero', [
            'eyebrow' => '',
            'title' => 'Discover Businesses. Find What You Need. ',
            'lead' => 'Explore stores on NovaxSuites POS & Marketplace, from retail shops and pharmacies to restaurants and wholesalers. Discover businesses, browse their products, and shop directly from their online stores.',
            'center' => true,
            'extraView' => 'landlord.partials.search-box',
            'extraData' => ['action' => route('businesses'), 'placeholder' => 'Search stores…', 'label' => 'Search stores'],
        ])
    </div>

    <section class="section">
        <div class="container">
            @if ($businesses->isEmpty())
                <div class="empty">
                    <i class="fas fa-store-slash" aria-hidden="true"></i>
                    <p>No stores found{{ request('search') ? ' for “' . request('search') . '”' : '' }}.</p>
                    @if (request('search'))
                        <a href="{{ route('businesses') }}" class="link-arrow" style="margin-top:1rem">View all stores <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </div>
            @else
                <div class="grid grid--4 businesses-grid">
                    @foreach ($businesses as $tenant)
                        @php $hasStore = (bool) $tenant->ecommerce_enabled; @endphp
                        <a href="{{ $hasStore ? route('marketplace.shop', $tenant->id) : route('marketplace.index') }}" class="card card--hover biz-card reveal">
                            @if ($tenant->logo_url)
                                <span class="biz-card__logo"><img src="{{ $tenant->logo_url }}" alt="" loading="lazy"></span>
                            @else
                                <span class="biz-card__initials" aria-hidden="true">{{ $tenant->initials }}</span>
                            @endif
                            <span class="biz-card__details">
                                <span class="biz-card__name">{{ $tenant->company_name }}</span>
                                @if ($tenant->marketplace_store_name && $tenant->marketplace_store_name !== $tenant->company_name)
                                    <span class="biz-card__store-name">{{ $tenant->marketplace_store_name }}</span>
                                @endif
                                @if ($tenant->marketplace_categories->isNotEmpty())
                                    <span class="biz-card__categories">{{ $tenant->marketplace_categories->take(2)->implode(' · ') }}</span>
                                @endif
                                @if ($hasStore)
                                    <span class="biz-card__products">{{ number_format($tenant->marketplace_product_count) }} {{ \Illuminate\Support\Str::plural('product', $tenant->marketplace_product_count) }}</span>
                                @endif
                            </span>
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

@push('head')
    <style>
        .businesses-page-hero .page-hero {
            background-image:
                linear-gradient(rgb(0 25 55 / .58), rgb(0 25 55 / .58)),
                url('{{ asset('images/businesses-hero-bg.png') }}');
            background-position: center;
            background-size: cover;
        }

        .businesses-grid .biz-card {
            align-items: stretch;
            gap: 0;
            overflow: hidden;
            padding: 0;
        }

        .businesses-grid .biz-card__logo,
        .businesses-grid .biz-card__initials {
            display: flex;
            width: 100%;
            height: 10rem;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            background: #f3f5f7;
        }

        .businesses-grid .biz-card__logo img {
            width: 100%;
            height: 100%;
            max-width: none;
            max-height: none;
            padding: 1rem;
            object-fit: contain;
        }

        .businesses-grid .biz-card__initials {
            color: var(--np-primary);
            font-size: 3rem;
        }

        .businesses-grid .biz-card__details,
        .businesses-grid .biz-card__cta {
            padding-right: 1rem;
            padding-left: 1rem;
            text-align: center;
        }

        .businesses-grid .biz-card__details {
            display: flex;
            min-height: 7rem;
            flex-direction: column;
            align-items: center;
            padding-top: 1rem;
        }

        .businesses-grid .biz-card__name { color: var(--np-navy); font-weight: 700; line-height: 1.3; }
        .businesses-grid .biz-card__store-name,
        .businesses-grid .biz-card__categories,
        .businesses-grid .biz-card__products { margin-top: .35rem; color: var(--muted); font-size: var(--text-sm); }
        .businesses-grid .biz-card__categories { color: var(--np-primary); }
        .businesses-grid .biz-card__cta { padding-top: .65rem; padding-bottom: 1rem; }
    </style>
@endpush
