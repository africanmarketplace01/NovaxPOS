{{--
    Header for every public page (included by landlord.layouts.public).
    Transparent over the landing hero; solid when $solid is true.
    Section links point at the landing page from every other page.
--}}
@php
    $onLanding = request()->routeIs('landing');
    $home = $onLanding ? '' : route('landing');
    $links = [
        ['href' => $home . '#features', 'label' => 'Features', 'route' => null],
        ['href' => $home . '#how-it-works', 'label' => 'How it works', 'route' => null],
        ['href' => route('pricing'), 'label' => 'Pricing', 'route' => 'pricing'],
        ['href' => route('marketplace.index'), 'label' => 'Marketplace', 'route' => 'marketplace.index'],
        ['href' => route('businesses'), 'label' => 'Businesses', 'route' => 'businesses'],
        ['href' => route('tutorials'), 'label' => 'Tutorials', 'route' => 'tutorials'],
    ];
    $solid = $solid ?? false;
@endphp

<header class="site-header @if ($solid) is-solid @endif" data-site-header @if ($solid) data-always-solid @endif>
    <div class="container site-header__inner">
        <a href="{{ route('landing') }}" class="site-header__brand" aria-label="{{ $siteN }} home">
            @if ($siteLogoUrl)
                <img src="{{ $siteLogoUrl }}" alt="{{ $siteN }}" class="site-header__logo">
            @else
                <span class="site-header__name">{{ $siteN }}<span>.</span></span>
            @endif
        </a>

        <nav class="site-nav" aria-label="Primary">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" @if ($link['route'] && request()->routeIs($link['route'])) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="site-header__actions">
            <a href="{{ route('superadmin.login') }}" class="btn btn--sm btn--ghost-light site-header__signin">Sign in</a>
            <a href="{{ route('register') }}" class="btn btn--sm btn--primary site-header__cta">Start free <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            <button type="button" class="site-header__burger" data-menu-toggle aria-controls="mobileMenu" aria-expanded="false" aria-label="Open menu">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div id="mobileMenu" class="mobile-menu" hidden>
        <nav class="container" aria-label="Mobile">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ route('superadmin.login') }}">Sign in</a>
            <a href="{{ route('register') }}" class="btn btn--primary btn--block">Start free</a>
        </nav>
    </div>
</header>
