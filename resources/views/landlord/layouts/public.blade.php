{{--
    Layout for every public (landlord) page. Branding — $site, $th, $siteN,
    $siteLogoUrl — comes from App\View\Composers\PublicSiteComposer.

    Sections:  title, description
               solid-header  (any value) — start the header solid instead of
                             transparent; use it on pages with no dark banner.
--}}
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>document.documentElement.classList.remove('no-js');</script>
    <title>@yield('title', $site['name'] . ' — ' . $site['tagline'])</title>
    <meta name="description" content="@yield('description', $site['name'] . ' is a multi-tenant cloud POS and marketplace. Manage sales, inventory, staff and analytics from one dashboard.')">

    {{-- Preconnect first so the font request doesn't pay for a fresh handshake. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $site['font']) }}:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('landlord/css/site.css') }}?v={{ @filemtime(public_path('landlord/css/site.css')) }}">

    {{-- Theme colours and font from the admin settings. --}}
    <style>
        :root {
            --np-primary: {{ $site['primary'] }};
            --np-primary-light: {{ $site['primary_light'] }};
            --np-accent: {{ $site['accent'] }};
            --np-accent-ink: {{ $site['accent_ink'] }};
            --np-accent-on-dark: {{ $site['accent_on_dark'] }};
            --np-navy: {{ $site['dark'] }};
            --np-font: "{{ $site['font'] }}";
        }
    </style>
    @stack('head')
</head>
<body>
    <a href="#main" class="skip-link">Skip to content</a>

    @include('landlord.partials.public-nav', ['solid' => View::hasSection('solid-header')])

    <main id="main" tabindex="-1">
        @yield('content')
    </main>

    @include('landlord.partials.public-footer')

    <script src="{{ asset('landlord/js/site.js') }}?v={{ @filemtime(public_path('landlord/js/site.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
