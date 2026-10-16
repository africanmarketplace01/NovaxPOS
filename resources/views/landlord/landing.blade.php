@extends('landlord.layouts.public')

@section('title', $siteN . ' — ' . $site['tagline'])
@section('description', $siteN . ' is a multi-tenant cloud POS and marketplace. Manage sales, inventory, staff and analytics from one dashboard.')

@php
    // Hero slides: label + icon per tab. Order matches partials/hero-mockups.
    $heroSlides = [
        ['Point of sale', 'fa-cash-register'],
        ['Inventory', 'fa-boxes-stacked'],
        ['Sales reports', 'fa-chart-line'],
        ['Online store', 'fa-store'],
        ['Delivery', 'fa-truck'],
    ];

    $features = [
        ['fa-cash-register', 'Lightning-fast POS', 'Process sales in seconds with barcode scanning, keyboard shortcuts, split payments and thermal receipt printing.'],
        ['fa-boxes-stacked', 'Inventory management', 'Track stock across unlimited warehouses, set low-stock alerts and manage batch expiry dates automatically.'],
        ['fa-chart-line', 'Real-time analytics', 'Instant insight into revenue, margins, top sellers and staff performance, with AI-driven forecasts.'],
        ['fa-users-gear', 'Multi-user & roles', 'Invite your team, assign role-based permissions and track every action with audit logs.'],
        ['fa-file-invoice-dollar', 'Invoicing & purchasing', 'Professional invoices, purchase orders and quotations, plus supplier payments and receivables.'],
        ['fa-store', 'E-commerce built in', 'Your online store syncs inventory, orders and customers with the POS in real time.'],
        ['fa-warehouse', 'Multi-warehouse', 'Transfer stock between locations, run warehouse-level reports and fulfil from the right place.'],
        ['fa-qrcode', 'Barcodes & QR codes', 'Generate and print barcodes and QR codes for every product, in all major standards.'],
        ['fa-globe', 'Multi-language & currency', 'Serve customers in 20+ languages with multi-currency pricing and regional tax rules.'],
        ['fa-users', 'Customer management', 'Purchase history, loyalty points, credit balances and targeted messaging in one place.'],
        ['fa-rotate-left', 'Returns & refunds', 'Process returns, exchanges and refunds in seconds; stock is restocked automatically.'],
        ['fa-wallet', 'Expense tracking', 'Log and categorise expenses and supplier payments for a clear view of operating costs.'],
    ];

    $journeys = [

        'merchant' => [
            'label' => "I'm a merchant",
            'icon' => 'fa-store',
            'steps' => [
                [
                    'Create your account & choose a plan',
                    'Choose the plan that fits your business, claim your unique store subdomain and set up your account in minutes. Start your free trial with no credit card required.'
                ],
                [
                    'Set up your POS & inventory',
                    'Add products, configure taxes and warehouses, set up payment methods, and invite your team. Use the guided onboarding process to get your store ready for business.'
                ],
                [
                    'Launch your online store',
                    'Publish your store on the shared marketplace with a single toggle. Your products become discoverable to shoppers looking for products from local businesses.'
                ],
                [
                    'Sell, manage & grow',
                    'Manage in-store sales and online orders from one dashboard. Coordinate deliveries with delivery partners, monitor transactions and keep your business running smoothly.'
                ],
            ],
            'cta' => [
                route('register'),
                'Start selling for free',
                'fa-rocket'
            ],
            'note' => 'No credit card required · Set up in minutes',
        ],

        'shopper' => [
            'label' => "I'm a shopper",
            'icon' => 'fa-bag-shopping',
            'steps' => [
                [
                    'Discover local stores & products',
                    'Explore the marketplace by category, price or store. Discover products from local businesses and find what you need in one convenient place.'
                ],
                [
                    'Shop across local stores',
                    'Add products to your cart and enjoy a convenient shopping experience. Where supported, combine products from multiple stores and review your order before checkout.'
                ],
                [
                    'Choose how to receive your order',
                    'Select an available delivery partner based on coverage, delivery rates and estimated delivery windows, or choose self-collection when offered.'
                ],
                [
                    'Checkout & track your order',
                    'Place your order and receive confirmation. Your selected store prepares your items while the store and delivery partner coordinate fulfillment.'
                ],
            ],
            'cta' => [
                route('marketplace.index'),
                'Explore the marketplace',
                'fa-bag-shopping'
            ],
            'note' => 'Browse without an account · Discover local businesses',
        ],

        'agency' => [
            'label' => "I'm a delivery partner",
            'icon' => 'fa-truck',
            'steps' => [
                ['Register Your Delivery Business', 'Complete your online application with your agency details, service areas, delivery rates, and operating hours. Upload your logo and tell us where you operate.'],
                ['Get Verified and Approved', 'Our team reviews your application to ensure your delivery service meets platform requirements. Once approved, your agency becomes discoverable to participating merchants on ' . $siteN . '.'],
                ['Connect with Merchants and Deliver', 'Get access to delivery opportunities from merchants within your coverage areas. Merchants or customer can assign deliveries to your agency, helping you connect with more businesses and serve more customers.'],
            ],
            'cta' => [route('delivery.register'), 'Become a Delivery Partner', 'fa-truck'],
            'note' => 'Free to register · Approval usually within 3 working days.',
        ],
    ];

    $faqs = [
        ['Can I try ' . $siteN . ' for free?', 'Yes. Eligible plans include a free trial with no credit card required: pick a plan with a trial, register your store and start using every feature straight away.'],
        ['How does the multi-tenant system work?', 'Each business gets its own isolated database and subdomain. Your data is completely separate from every other business on the platform.'],
        ['Can I list my store on the marketplace?', 'Yes. One toggle in your store settings publishes your products to the shared marketplace, so customers can buy without visiting your subdomain.'],
        ['How do delivery agencies work?', "Approved delivery agencies appear at checkout on every merchant's store. The customer picks a courier and the merchant assigns the delivery. Agencies apply online and are vetted before going live."],
        ['Can I upgrade or downgrade my plan?', 'Yes. Upgrades take effect immediately and unlock higher limits right away. Downgrades apply from your next billing cycle.'],
        ['Is my data safe and backed up?', 'Each tenant has an isolated database with regular automated backups, and all traffic is served over HTTPS.'],
    ];

    $yearlySavingsLabel = $th['yearly_savings_label'] ?? '';
@endphp

@section('content')
    {{-- ================================================================ --}}
    {{-- Hero — full window height over a slider of the product's screens --}}
    {{-- ================================================================ --}}
    <section class="hero" data-hero data-interval="6000" aria-roledescription="carousel" aria-label="{{ $siteN }} screens">
        <div class="hero__glow" aria-hidden="true"></div>
        <div class="hero__grid" aria-hidden="true"></div>
        <div class="hero__stage">
            @include('landlord.partials.hero-mockups')
        </div>

        <div class="container hero__body">
            <div class="hero__copy">
                <p class="hero__badge"><span class="hero__badge-dot" aria-hidden="true"></span> BUILT FOR RETAILERS,
                    PHARMACIES, RESTAURANTS & WHOLESALERS</p>
                <h1 class="hero__title">Run Your Business <em>Smarter</em>. Sell Beyond Your Store.</h1>
                <p class="hero__lead">The all-in-one cloud POS and marketplace that brings sales, inventory, customers,
                    staff and multiple branches together. Gain real-time business insights, simplify daily operations and
                    connect with more customers — all from one powerful platform.</p>
                <div class="hero__actions">
                    <a href="{{ route('register') }}" class="btn btn--accent btn--lg">Get Started for Free <i
                            class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ route('pricing') }}" class="btn btn--ghost-light btn--lg">View pricing</a>
                </div>
            </div>
        </div>

        <div class="container hero__tabs">
            <div class="hero__tabs-row">
                <button type="button" class="hero__toggle" data-hero-toggle aria-label="Pause slideshow"><i
                        class="fas fa-pause" aria-hidden="true"></i></button>
                <div class="hero__tablist" role="tablist" aria-label="Choose a screen">
                    @foreach ($heroSlides as [$label, $icon])
                        <button type="button" role="tab" class="hero__tab @if ($loop->first) is-active @endif" data-slide-tab
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            <span class="hero__tab-track"><span class="hero__tab-bar"></span></span>
                            <span class="hero__tab-label"><i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Businesses on the platform --}}
    {{-- ================================================================ --}}
    @if ($trustedTenants->isNotEmpty())
        <section class="section section--white" id="businesses" aria-labelledby="trusted-title">
            <div class="container">
                <div class="section-head section-head--center reveal">
                    <p class="eyebrow">Trusted across industries</p>
                    <h2 id="trusted-title" class="section-title">Businesses running on {{ $siteN }}</h2>
                </div>
                <div class="grid grid--4">
                    @foreach ($trustedTenants->take(8) as $tenant)
                        @php $hasStore = (bool) $tenant->ecommerce_enabled; @endphp
                        <a href="{{ $hasStore ? route('marketplace.shop', $tenant->id) : route('marketplace.index') }}"
                            class="card card--hover biz-card reveal">
                            @if ($tenant->logo_url)
                                <span class="biz-card__logo"><img src="{{ $tenant->logo_url }}" alt="" loading="lazy"></span>
                            @else
                                <span class="biz-card__initials" aria-hidden="true">{{ $tenant->initials }}</span>
                            @endif
                            <span class="biz-card__name">{{ $tenant->company_name }}</span>
                            <span class="biz-card__cta">{{ $hasStore ? 'Visit store' : 'Explore marketplace' }} <i
                                    class="fas fa-arrow-right" aria-hidden="true"></i></span>
                        </a>
                    @endforeach
                </div>
                @if ($trustedTenants->count() > 8)
                    <p class="text-center" style="margin-top:2rem"><a href="{{ route('businesses') }}" class="link-arrow">View all
                            businesses <i class="fas fa-arrow-right" aria-hidden="true"></i></a></p>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================================================ --}}
    {{-- Features --}}
    {{-- ================================================================ --}}
    <section class="section" id="features" aria-labelledby="features-title">
        <div class="container">
            <div class="section-head reveal">
                <p class="eyebrow">Features</p>
                <h2 id="features-title" class="section-title">Everything your business needs</h2>
                <p class="section-sub">One platform for every part of your business, from the counter to the cloud.</p>
            </div>
            <div class="grid grid--3">
                @foreach ($features as [$icon, $title, $desc])
                    <article class="card card--pad card--hover feature reveal">
                        <span class="icon-tile"><i class="fas {{ $icon }}" aria-hidden="true"></i></span>
                        <h3>{{ $title }}</h3>
                        <p>{{ $desc }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- How it works — three journeys --}}
    {{-- ================================================================ --}}
    <section class="section section--white" id="how-it-works" aria-labelledby="hiw-title">
        <div class="container">
            <div class="section-head reveal">
                <p class="eyebrow">How it works</p>
                <h2 id="hiw-title" class="section-title">One Platform. Three Ways to Grow.</h2>
                <p class="section-sub">Whether you're growing your business, shopping from local stores, or delivering
                    orders to customers, {{ $siteN }} connects you to the tools and opportunities you need — all in one
                    platform.</p>
            </div>

            <div class="tabs" role="tablist" aria-label="Choose your journey" data-tabs>
                @foreach ($journeys as $key => $journey)
                    <button type="button" class="tabs__btn" role="tab" id="hiw-tab-{{ $key }}" aria-controls="hiw-{{ $key }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? 0 : -1 }}">
                        <i class="fas {{ $journey['icon'] }}" aria-hidden="true"></i> {{ $journey['label'] }}
                    </button>
                @endforeach
            </div>

            @foreach ($journeys as $key => $journey)
                <div class="tab-panel" id="hiw-{{ $key }}" role="tabpanel" aria-labelledby="hiw-tab-{{ $key }}" @unless ($loop->first) hidden @endunless style="margin-top:2.5rem">
                    <ol @class(['grid', 'grid--4' => count($journey['steps']) === 4, 'grid--3' => count($journey['steps']) === 3, 'steps'])>
                        @foreach ($journey['steps'] as [$title, $desc])
                            <li class="step reveal">
                                <span class="step__num">{{ $loop->iteration }}</span>
                                <h3>{{ $title }}</h3>
                                <p>{{ $desc }}</p>
                            </li>
                        @endforeach
                    </ol>
                    <div class="steps-cta">
                        <a href="{{ $journey['cta'][0] }}" class="btn btn--primary btn--lg"><i
                                class="fas {{ $journey['cta'][2] }}" aria-hidden="true"></i> {{ $journey['cta'][1] }}</a>
                        <span class="muted">{{ $journey['note'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Stats --}}
    {{-- ================================================================ --}}
    <section class="section section--navy" aria-label="Platform highlights">
        <div class="container stats">
            <div class="reveal">
                <p class="stat__value">For<span>All</span></p>
                <p class="stat__label">Selling, shopping & delivery</p>
            </div>
            <div class="reveal">
                <p class="stat__value">Simplified</p>
                <p class="stat__label">Sales, inventory & order management</p>
            </div>
            <div class="reveal">
                <p class="stat__value">Connected</p>
                <p class="stat__label">Merchants, shoppers & delivery partners</p>
            </div>
            <div class="reveal">
                <p class="stat__value">24<span>/7</span></p>
                <p class="stat__label">Manage your business from anywhere</p>
            </div>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Commerce ecosystem --}}
    {{-- ================================================================ --}}
    <section class="section" id="ecosystem" aria-labelledby="eco-title">
        <div class="container">

            <div class="section-head reveal">
                <p class="eyebrow">One connected commerce ecosystem</p>

                <h2 id="eco-title" class="section-title">
                    Sell Locally. Reach Further. Grow Smarter.
                </h2>

                <p class="section-sub">
                    {{ $siteN }} brings your point of sale, online storefront,
                    shared marketplace and delivery network together.
                    Manage your business, reach more customers and simplify
                    order fulfillment from one connected platform.
                </p>
            </div>

            {{-- How the ecosystem works --}}
            <ol class="flow reveal" aria-label="How the commerce ecosystem works">

                @foreach ([
                        [
                            'fa-store',
                            'Your business',
                            'Merchant',
                            'Lists products and manages stock'
                        ],
                        [
                            'fa-bag-shopping',
                            'Shared marketplace',
                            'Shopping platform',
                            'Connects shoppers with products'
                        ],
                        [
                            'fa-user',
                            'Your customer',
                            'Shopper',
                            'Places an order and selects delivery'
                        ],
                        [
                            'fa-truck',
                            'Delivery network',
                            'Delivery partner',
                            'Fulfills the assigned delivery'
                        ]
                    ] as [$icon, $label, $role, $action])

                    <li class="card flow__node">
                        <span class="icon-tile icon-tile--solid">
                            <i class="fas {{ $icon }}" aria-hidden="true"></i>
                        </span>

                        <p class="flow__label">{{ $label }}</p>
                        <p class="flow__role">{{ $role }}</p>

                        <p class="flow__arrow">
                            {{ $action }}

                            @unless ($loop->last)
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            @endunless
                        </p>
                    </li>

                @endforeach

            </ol>

            {{-- Ecosystem features --}}
            <div class="grid grid--2">

                {{-- Marketplace --}}
                <article class="card card--pad card--pad-lg reveal">

                    <p class="eyebrow">For merchants &amp; shoppers</p>

                    <h3 style="margin-top:.5rem;font-size:var(--text-2xl)">
                        Your Store. A Bigger Marketplace.
                    </h3>

                    <p class="muted" style="margin-top:.75rem">
                        Take your business beyond your physical location.
                        Launch your online storefront and make your products
                        discoverable alongside other businesses on the
                        {{ $siteN }} marketplace, without building a separate
                        website.
                    </p>

                    <ul class="checklist">
                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Your own online storefront</strong>:
                                showcase your business and products online
                            </span>
                        </li>

                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Connected inventory</strong>:
                                keep POS and online stock synchronized
                            </span>
                        </li>

                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Marketplace visibility</strong>:
                                help more shoppers discover your products
                            </span>
                        </li>

                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Unified order management</strong>:
                                manage sales and online orders in one place
                            </span>
                        </li>

                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Easy sharing</strong>:
                                promote your store with shareable links
                                and QR codes
                            </span>
                        </li>
                    </ul>

                    <a href="{{ route('marketplace.index') }}" class="btn btn--primary" style="margin-top:1.5rem">
                        <i class="fas fa-store" aria-hidden="true"></i>
                        Explore the marketplace
                    </a>

                </article>

                {{-- Delivery network --}}
                <article class="card card--pad card--pad-lg reveal">

                    <p class="eyebrow">For delivery businesses</p>

                    <h3 style="margin-top:.5rem;font-size:var(--text-2xl)">
                        More Connections. More Delivery Opportunities.
                    </h3>

                    <p class="muted" style="margin-top:.75rem">
                        Connect your delivery agency with merchants on
                        {{ $siteN }}. Approved partners can become available
                        to merchants and shoppers in their service areas,
                        helping businesses fulfill orders more conveniently.
                    </p>

                    <ul class="checklist">
                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Application review</strong>:
                                build trust through the partner approval process
                            </span>
                        </li>

                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Customer courier choice</strong>:
                                let shoppers choose from available delivery partners
                            </span>
                        </li>

                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Service-area matching</strong>:
                                connect delivery options with supported locations
                            </span>
                        </li>

                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Flexible delivery rates</strong>:
                                configure pricing for your service
                            </span>
                        </li>

                        <li>
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span>
                                <strong>Grow your network</strong>:
                                connect with more participating merchants
                            </span>
                        </li>
                    </ul>

                    <a href="{{ route('delivery.register') }}" class="btn btn--outline" style="margin-top:1.5rem">
                        <i class="fas fa-truck" aria-hidden="true"></i>
                        Join the delivery network
                    </a>

                </article>

            </div>

            {{-- Conversion banner --}}
            <div class="banner reveal">

                <div>
                    <h3>
                        Take Your Business Beyond the Counter
                    </h3>

                    <p>
                        Manage sales with your POS, showcase products through
                        your online store, reach shoppers on the marketplace
                        and connect with delivery partners through one platform.
                        Everything your business needs to take the next step.
                    </p>
                </div>

                <div class="banner__actions">
                    <a href="{{ route('register') }}" class="btn btn--accent">
                        <i class="fas fa-rocket" aria-hidden="true"></i>
                        Start selling today
                    </a>

                    <a href="{{ route('marketplace.index') }}" class="btn btn--ghost-light">
                        Shop the marketplace
                    </a>
                </div>

            </div>

        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Pricing --}}
    {{-- ================================================================ --}}
    <section class="section section--white" id="pricing" aria-labelledby="pricing-title">
        <div class="container">
            <div class="section-head section-head--center reveal">
                <p class="eyebrow">Pricing</p>
                <h2 id="pricing-title" class="section-title">Simple, transparent pricing</h2>
                <p class="section-sub">Start free and upgrade as you grow. No hidden fees, no lock-in.</p>
                <div style="margin-top:1.75rem">
                    @include('landlord.partials.billing-toggle', ['yearlySavingsLabel' => $yearlySavingsLabel])
                </div>
            </div>

            @if ($packages->isEmpty())
                <div class="empty"><i class="fas fa-box-open" aria-hidden="true"></i>
                    <p>No plans available yet. Check back soon.</p>
                </div>
            @else
                <div @class(['plans', 'plans--4' => $packages->count() >= 4, 'plans--3' => $packages->count() === 3])
                    data-billing="monthly">
                    @foreach ($packages as $plan)
                        @include('landlord.partials.plan-card', ['plan' => $plan, 'promotion' => $promotion, 'featureLimit' => 5])
                    @endforeach
                </div>
            @endif
            <p class="text-center" style="margin-top:2rem"><a href="{{ route('pricing') }}" class="link-arrow">Compare every
                    plan feature <i class="fas fa-arrow-right" aria-hidden="true"></i></a></p>
            @include('landlord.partials.seller-callout')
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Testimonials --}}
    {{-- ================================================================ --}}
    @if ($testimonials->isNotEmpty())
        <section class="section" id="testimonials" aria-labelledby="testi-title">
            <div class="container">
                <div class="section-head reveal">
                    <p class="eyebrow">Testimonials</p>
                    <h2 id="testi-title" class="section-title">Loved by businesses</h2>
                </div>
                <div class="grid grid--3">
                    @foreach ($testimonials as $t)
                        <figure class="card card--pad quote reveal">
                            <p class="quote__stars" aria-label="{{ $t->stars ?? 5 }} out of 5 stars">
                                @for ($s = 1; $s <= 5; $s++){{ $s <= ($t->stars ?? 5) ? '★' : '☆' }}@endfor</p>
                            <blockquote>&ldquo;{{ $t->body }}&rdquo;</blockquote>
                            <figcaption>
                                @if ($t->avatar_url)
                                    <img src="{{ $t->avatar_url }}" alt="" class="quote__av" loading="lazy">
                                @else
                                    <span class="quote__av"
                                        aria-hidden="true">{{ strtoupper($t->author_initials ?: substr($t->author_name, 0, 1)) }}</span>
                                @endif
                                <span>
                                    <span class="quote__name">{{ $t->author_name }}</span><br>
                                    @if ($t->author_role)<span class="quote__role">{{ $t->author_role }}</span>@endif
                                </span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================================================================ --}}
    {{-- FAQ --}}
    {{-- ================================================================ --}}
    <section class="section @if ($testimonials->isNotEmpty()) section--white @endif" id="faq" aria-labelledby="faq-title">
        <div class="container faq-layout">
            <div class="reveal">
                <p class="eyebrow">FAQ</p>
                <h2 id="faq-title" class="section-title">Frequently asked questions</h2>
                <p class="section-sub">Still unsure? <a href="{{ route('register') }}" class="link-arrow">Start a free
                        trial</a> and see for yourself.</p>
            </div>
            <div class="faq reveal">
                @foreach ($faqs as [$question, $answer])
                    <details>
                        <summary>{{ $question }} <i class="fas fa-plus" aria-hidden="true"></i></summary>
                        <p>{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Final call to action --}}
    {{-- ================================================================ --}}
    <section class="section cta-band">
        <div class="container reveal">
            <h2>Ready to grow your business?</h2>
            <p>Join the businesses using {{ $siteN }} to sell smarter, manage inventory and reach customers everywhere.</p>
            <div class="cta-band__actions">
                <a href="{{ route('register') }}" class="btn btn--accent btn--lg"><i class="fas fa-rocket"
                        aria-hidden="true"></i> Start your free trial</a>
                <a href="{{ route('marketplace.index') }}" class="btn btn--ghost-light btn--lg">Visit marketplace</a>
            </div>
            <p class="cta-band__note">No credit card required &middot; Free trial available &middot; Cancel anytime</p>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Promotion pop-up (shown once per browser session while live) --}}
    {{-- ================================================================ --}}
    @if ($promotion && $promotion->show_modal)
        <div class="promo" id="promoModal" hidden data-promo-id="{{ $promotion->id }}"
            data-ends="{{ $promotion->endsAtIso() }}">
            <div class="promo__box" role="dialog" aria-modal="true" aria-labelledby="promoTitle">
                <button type="button" class="promo__close" data-promo-close aria-label="Close">&times;</button>
                <div class="promo__head">
                    <span class="promo__badge">{{ $promotion->label() }}</span>
                    <h2 id="promoTitle">{{ $promotion->title }}</h2>
                </div>
                <div class="promo__body">
                    <p>{{ $promotion->description ?: 'Limited-time offer on every plan. Lock in your discount before it ends!' }}
                    </p>
                    <div class="countdown" id="promoCountdown">
                        <div><b data-cd="d">--</b><span>Days</span></div>
                        <div><b data-cd="h">--</b><span>Hours</span></div>
                        <div><b data-cd="m">--</b><span>Mins</span></div>
                        <div><b data-cd="s">--</b><span>Secs</span></div>
                    </div>
                    <a href="#pricing" class="btn btn--primary btn--block" data-promo-close><i class="fas fa-tags"
                            aria-hidden="true"></i> View discounted plans</a>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @if ($promotion && $promotion->show_modal)
        <script>
            (function () {
                var modal = document.getElementById('promoModal');
                if (!modal) return;
                var endsAt = new Date(modal.getAttribute('data-ends')).getTime();
                var key = 'promo_dismissed_' + modal.getAttribute('data-promo-id');

                function close() {
                    modal.hidden = true;
                    try { sessionStorage.setItem(key, '1'); } catch (e) { }
                }

                var dismissed = false;
                try { dismissed = !!sessionStorage.getItem(key); } catch (e) { }
                if (!dismissed && (!endsAt || Date.now() < endsAt)) {
                    setTimeout(function () { modal.hidden = false; }, 900);
                }

                modal.querySelectorAll('[data-promo-close]').forEach(function (el) { el.addEventListener('click', close); });
                modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });

                if (endsAt) {
                    var cd = document.getElementById('promoCountdown');
                    var tick = function () {
                        var diff = endsAt - Date.now();
                        if (diff <= 0) { modal.hidden = true; return; }
                        cd.querySelector('[data-cd=d]').textContent = Math.floor(diff / 86400000);
                        cd.querySelector('[data-cd=h]').textContent = ('0' + Math.floor((diff % 86400000) / 3600000)).slice(-2);
                        cd.querySelector('[data-cd=m]').textContent = ('0' + Math.floor((diff % 3600000) / 60000)).slice(-2);
                        cd.querySelector('[data-cd=s]').textContent = ('0' + Math.floor((diff % 60000) / 1000)).slice(-2);
                    };
                    tick();
                    setInterval(tick, 1000);
                }
            })();
        </script>
    @endif
@endpush
