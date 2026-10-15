{{-- Footer for every public page (included by landlord.layouts.public). --}}
<footer class="site-footer">
    <div class="container site-footer__grid">
        <div>
            @if ($siteLogoUrl)
                <img src="{{ $siteLogoUrl }}" alt="{{ $siteN }}" class="site-footer__logo" loading="lazy">
            @else
                <p class="site-footer__name">{{ $siteN }}<span>.</span></p>
            @endif
            <p class="site-footer__about">The complete cloud POS and marketplace for growing businesses: sell in-store, online and everywhere in between.</p>
        </div>

        <div>
            <p class="site-footer__title">Product</p>
            <ul>
                <li><a href="{{ route('landing') }}#features">Features</a></li>
                <li><a href="{{ route('pricing') }}">Pricing</a></li>
                <li><a href="{{ route('tutorials') }}">Tutorials</a></li>
                <li><a href="{{ route('register') }}">Start free trial</a></li>
            </ul>
        </div>

        <div>
            <p class="site-footer__title">Marketplace</p>
            <ul>
                <li><a href="{{ route('marketplace.index') }}">Shop the marketplace</a></li>
                <li><a href="{{ route('sell') }}">Sell on the marketplace (free)</a></li>
                <li><a href="{{ route('businesses') }}">Businesses</a></li>
                <li><a href="{{ route('delivery.register') }}">Become a delivery partner</a></li>
            </ul>
        </div>

        <div>
            <p class="site-footer__title">Account</p>
            <ul>
                <li><a href="{{ route('register') }}">Open a store</a></li>
                <li><a href="{{ route('superadmin.login') }}">Sign in</a></li>
            </ul>
        </div>
    </div>

    <div class="container site-footer__bottom">
        <span>&copy; {{ date('Y') }} {{ $siteN }}. All rights reserved. <span class="site-footer__family">A BridgeNovaX product.</span></span>
        <div class="site-footer__badges">
            <span><i class="fas fa-shield-halved" aria-hidden="true"></i> Secure</span>
            <span><i class="fas fa-lock" aria-hidden="true"></i> Encrypted</span>
        </div>
    </div>
</footer>
