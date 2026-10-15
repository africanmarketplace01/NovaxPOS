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
            'label' => "I'm a merchant", 'icon' => 'fa-store',
            'steps' => [
                ['Register & choose your plan', 'Pick a subscription, claim your subdomain and create your account in under a minute. No credit card for the free trial.'],
                ['Configure your POS', 'Add products, taxes, warehouses and payment methods. Invite your team and customise receipts with the onboarding wizard.'],
                ['Go live on the marketplace', 'Turn on your public store in one toggle. Products sync to the shared marketplace so you reach customers beyond your door.'],
                ['Sell, fulfil & grow', 'Sell in-store, manage marketplace orders, assign delivery partners and track it all from one dashboard.'],
            ],
            'cta' => [route('register'), 'Open your store for free', 'fa-rocket'],
            'note' => 'No credit card required · Live in minutes',
        ],
        'shopper' => [
            'label' => "I'm a shopper", 'icon' => 'fa-bag-shopping',
            'steps' => [
                ['Discover local products', 'Browse the marketplace by category, price or store. Every product comes from a real local business.'],
                ['Build your cart', 'Add products from several stores to one cart. Checkout handles the rest.'],
                ['Choose your delivery', 'Pick a vetted delivery partner at checkout, with coverage, delivery window and rate shown up front, or collect it yourself.'],
                ['Receive your order', 'Get instant confirmation while the store and the courier coordinate the delivery.'],
            ],
            'cta' => [route('marketplace.index'), 'Browse the marketplace', 'fa-bag-shopping'],
            'note' => 'No account needed to browse · Free to shop',
        ],
        'agency' => [
            'label' => "I'm a delivery partner", 'icon' => 'fa-truck',
            'steps' => [
                ['Apply online in minutes', 'Enter your agency details, coverage areas, base rate and per-km pricing. Upload your logo and set your delivery window.'],
                ['Get vetted & approved', 'The platform team reviews your application. Once approved you are visible to every merchant on ' . $siteN . '.'],
                ['Receive orders & deliver', 'Appear at checkout across every store in your coverage area. Customers choose you, merchants assign the job.'],
            ],
            'cta' => [route('delivery.register'), 'Register your agency', 'fa-truck'],
            'note' => 'Free to register · Approval usually within 24 hours',
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
                <p class="hero__badge"><span class="hero__badge-dot" aria-hidden="true"></span> Trusted by retailers, pharmacies, restaurants &amp; wholesalers</p>
                <h1 class="hero__title">The complete cloud <em>POS &amp; marketplace</em> for growing businesses</h1>
                <p class="hero__lead">Manage sales, inventory, customers, staff and every branch from one platform. Real-time reports and AI-driven insights help you cut stock losses and grow revenue.</p>
                <div class="hero__actions">
                    <a href="{{ route('register') }}" class="btn btn--accent btn--lg">Start free trial <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ route('pricing') }}" class="btn btn--ghost-light btn--lg">View pricing</a>
                </div>

                {{-- A head-count only persuades once it is a real number. --}}
                @if ($trustedTenants->count() >= 10)
                    <div class="hero__proof">
                        <div class="avatar-stack" aria-hidden="true">
                            @foreach ($trustedTenants->take(5) as $tenant)
                                @if ($tenant->logo_url)
                                    <img src="{{ $tenant->logo_url }}" alt="">
                                @else
                                    <span>{{ $tenant->initials }}</span>
                                @endif
                            @endforeach
                        </div>
                        <p class="hero__proof-text">
                            <span class="hero__stars" aria-hidden="true">★★★★★</span><br>
                            Running <strong>{{ number_format($trustedTenants->count()) }}+ businesses</strong> today
                        </p>
                    </div>
                @else
                    <p class="hero__proof hero__proof-text"><i class="fas fa-circle-check" style="color:var(--np-accent)" aria-hidden="true"></i> No credit card required &middot; Live in minutes &middot; Cancel anytime</p>
                @endif
            </div>
        </div>

        <div class="container hero__tabs">
            <div class="hero__tabs-row">
                <button type="button" class="hero__toggle" data-hero-toggle aria-label="Pause slideshow"><i class="fas fa-pause" aria-hidden="true"></i></button>
                <div class="hero__tablist" role="tablist" aria-label="Choose a screen">
                    @foreach ($heroSlides as [$label, $icon])
                        <button type="button" role="tab" class="hero__tab @if ($loop->first) is-active @endif" data-slide-tab aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            <span class="hero__tab-track"><span class="hero__tab-bar"></span></span>
                            <span class="hero__tab-label"><i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Businesses on the platform                                      --}}
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
                @if ($trustedTenants->count() > 8)
                    <p class="text-center" style="margin-top:2rem"><a href="{{ route('businesses') }}" class="link-arrow">View all businesses <i class="fas fa-arrow-right" aria-hidden="true"></i></a></p>
                @endif
            </div>
        </section>
    @endif

    {{-- ================================================================ --}}
    {{-- Features                                                        --}}
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
    {{-- How it works — three journeys                                   --}}
    {{-- ================================================================ --}}
    <section class="section section--white" id="how-it-works" aria-labelledby="hiw-title">
        <div class="container">
            <div class="section-head reveal">
                <p class="eyebrow">How it works</p>
                <h2 id="hiw-title" class="section-title">One platform, three journeys</h2>
                <p class="section-sub">Opening a business, shopping from local stores or running a courier service: {{ $siteN }} has a clear path for you.</p>
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
                        <a href="{{ $journey['cta'][0] }}" class="btn btn--primary btn--lg"><i class="fas {{ $journey['cta'][2] }}" aria-hidden="true"></i> {{ $journey['cta'][1] }}</a>
                        <span class="muted">{{ $journey['note'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Stats                                                           --}}
    {{-- ================================================================ --}}
    <section class="section section--navy" aria-label="Platform highlights">
        <div class="container stats">
            <div class="reveal"><p class="stat__value">24<span>/7</span></p><p class="stat__label">Cloud access</p></div>
            <div class="reveal"><p class="stat__value">AI</p><p class="stat__label">Business insights</p></div>
            <div class="reveal"><p class="stat__value">Multi</p><p class="stat__label">Branch management</p></div>
            <div class="reveal"><p class="stat__value">99.9<span>%</span></p><p class="stat__label">System availability</p></div>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Commerce ecosystem                                              --}}
    {{-- ================================================================ --}}
    <section class="section" id="ecosystem" aria-labelledby="eco-title">
        <div class="container">
            <div class="section-head reveal">
                <p class="eyebrow">Commerce ecosystem</p>
                <h2 id="eco-title" class="section-title">Beyond POS: build your digital business</h2>
                <p class="section-sub">{{ $siteN }} connects merchants, customers and delivery partners in one ecosystem. Sell in-store, online and across channels while trusted partners handle delivery.</p>
            </div>

            <ol class="flow reveal" aria-label="How an order flows">
                @foreach ([['fa-store', 'Your store', 'Merchant', 'Publishes products'], ['fa-bag-shopping', 'Marketplace', 'Platform', 'Takes orders'], ['fa-user', 'Customer', 'End user', 'Chooses a courier'], ['fa-truck', 'Delivery partner', 'Logistics', 'Delivers the order']] as [$icon, $label, $role, $action])
                    <li class="card flow__node">
                        <span class="icon-tile icon-tile--solid"><i class="fas {{ $icon }}" aria-hidden="true"></i></span>
                        <p class="flow__label">{{ $label }}</p>
                        <p class="flow__role">{{ $role }}</p>
                        <p class="flow__arrow">{{ $action }} @unless ($loop->last)<i class="fas fa-arrow-right" aria-hidden="true"></i>@endunless</p>
                    </li>
                @endforeach
            </ol>

            <div class="grid grid--2">
                <article class="card card--pad card--pad-lg reveal">
                    <p class="eyebrow">For merchants &amp; customers</p>
                    <h3 style="margin-top:.5rem;font-size:var(--text-2xl)">Multi-vendor marketplace</h3>
                    <p class="muted" style="margin-top:.75rem">Every business on {{ $siteN }} gets an online storefront and a place on the shared marketplace, with no separate website to build.</p>
                    <ul class="checklist">
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Instant online store</strong>: launch your storefront in minutes</span></li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Real-time inventory sync</strong> between POS and marketplace</span></li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>More visibility</strong> with shoppers already on the platform</span></li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Integrated checkout</strong> and order management</span></li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Shareable store links &amp; QR codes</strong></span></li>
                    </ul>
                    <a href="{{ route('marketplace.index') }}" class="btn btn--primary" style="margin-top:1.5rem"><i class="fas fa-store" aria-hidden="true"></i> Explore the marketplace</a>
                </article>
                <article class="card card--pad card--pad-lg reveal">
                    <p class="eyebrow">For logistics providers</p>
                    <h3 style="margin-top:.5rem;font-size:var(--text-2xl)">Integrated delivery network</h3>
                    <p class="muted" style="margin-top:.75rem">Delivery partners receive jobs from merchants across the platform. Customers choose their courier at checkout; merchants skip the logistics headache.</p>
                    <ul class="checklist">
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Verified partners</strong>: every agency is reviewed first</span></li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Courier choice at checkout</strong> for customers</span></li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Coverage-based matching</strong> per order</span></li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Flexible pricing</strong> that fits your business</span></li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i><span><strong>Multi-currency</strong>, ready for new markets</span></li>
                    </ul>
                    <a href="{{ route('delivery.register') }}" class="btn btn--outline" style="margin-top:1.5rem"><i class="fas fa-truck" aria-hidden="true"></i> Become a delivery partner</a>
                </article>
            </div>

            <div class="banner reveal">
                <div>
                    <h3>Turn your store into an online business</h3>
                    <p>A POS, a professional storefront, marketplace exposure and trusted delivery partners, all from one platform. No extra software, no integrations to maintain.</p>
                </div>
                <div class="banner__actions">
                    <a href="{{ route('register') }}" class="btn btn--accent"><i class="fas fa-rocket" aria-hidden="true"></i> Start selling today</a>
                    <a href="{{ route('marketplace.index') }}" class="btn btn--ghost-light">Visit marketplace</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Pricing                                                         --}}
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
                <div class="empty"><i class="fas fa-box-open" aria-hidden="true"></i><p>No plans available yet. Check back soon.</p></div>
            @else
                <div @class(['plans', 'plans--4' => $packages->count() >= 4, 'plans--3' => $packages->count() === 3]) data-billing="monthly">
                    @foreach ($packages as $plan)
                        @include('landlord.partials.plan-card', ['plan' => $plan, 'promotion' => $promotion, 'featureLimit' => 5])
                    @endforeach
                </div>
            @endif
            <p class="text-center" style="margin-top:2rem"><a href="{{ route('pricing') }}" class="link-arrow">Compare every plan feature <i class="fas fa-arrow-right" aria-hidden="true"></i></a></p>
            @include('landlord.partials.seller-callout')
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Testimonials                                                    --}}
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
                            <p class="quote__stars" aria-label="{{ $t->stars ?? 5 }} out of 5 stars">@for ($s = 1; $s <= 5; $s++){{ $s <= ($t->stars ?? 5) ? '★' : '☆' }}@endfor</p>
                            <blockquote>&ldquo;{{ $t->body }}&rdquo;</blockquote>
                            <figcaption>
                                @if ($t->avatar_url)
                                    <img src="{{ $t->avatar_url }}" alt="" class="quote__av" loading="lazy">
                                @else
                                    <span class="quote__av" aria-hidden="true">{{ strtoupper($t->author_initials ?: substr($t->author_name, 0, 1)) }}</span>
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
    {{-- FAQ                                                             --}}
    {{-- ================================================================ --}}
    <section class="section @if ($testimonials->isNotEmpty()) section--white @endif" id="faq" aria-labelledby="faq-title">
        <div class="container faq-layout">
            <div class="reveal">
                <p class="eyebrow">FAQ</p>
                <h2 id="faq-title" class="section-title">Frequently asked questions</h2>
                <p class="section-sub">Still unsure? <a href="{{ route('register') }}" class="link-arrow">Start a free trial</a> and see for yourself.</p>
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
    {{-- Final call to action                                            --}}
    {{-- ================================================================ --}}
    <section class="section cta-band">
        <div class="container reveal">
            <h2>Ready to grow your business?</h2>
            <p>Join the businesses using {{ $siteN }} to sell smarter, manage inventory and reach customers everywhere.</p>
            <div class="cta-band__actions">
                <a href="{{ route('register') }}" class="btn btn--accent btn--lg"><i class="fas fa-rocket" aria-hidden="true"></i> Start your free trial</a>
                <a href="{{ route('marketplace.index') }}" class="btn btn--ghost-light btn--lg">Visit marketplace</a>
            </div>
            <p class="cta-band__note">No credit card required &middot; Free trial available &middot; Cancel anytime</p>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- Promotion pop-up (shown once per browser session while live)    --}}
    {{-- ================================================================ --}}
    @if ($promotion && $promotion->show_modal)
        <div class="promo" id="promoModal" hidden data-promo-id="{{ $promotion->id }}" data-ends="{{ $promotion->endsAtIso() }}">
            <div class="promo__box" role="dialog" aria-modal="true" aria-labelledby="promoTitle">
                <button type="button" class="promo__close" data-promo-close aria-label="Close">&times;</button>
                <div class="promo__head">
                    <span class="promo__badge">{{ $promotion->label() }}</span>
                    <h2 id="promoTitle">{{ $promotion->title }}</h2>
                </div>
                <div class="promo__body">
                    <p>{{ $promotion->description ?: 'Limited-time offer on every plan. Lock in your discount before it ends!' }}</p>
                    <div class="countdown" id="promoCountdown">
                        <div><b data-cd="d">--</b><span>Days</span></div>
                        <div><b data-cd="h">--</b><span>Hours</span></div>
                        <div><b data-cd="m">--</b><span>Mins</span></div>
                        <div><b data-cd="s">--</b><span>Secs</span></div>
                    </div>
                    <a href="#pricing" class="btn btn--primary btn--block" data-promo-close><i class="fas fa-tags" aria-hidden="true"></i> View discounted plans</a>
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
                    try { sessionStorage.setItem(key, '1'); } catch (e) {}
                }

                var dismissed = false;
                try { dismissed = !!sessionStorage.getItem(key); } catch (e) {}
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
