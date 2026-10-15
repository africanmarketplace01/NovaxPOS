<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — NovaxPOS Admin</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @php
        /* Resolve theme from DB (cached) or fall back to LinkedPOS brand defaults */
        try {
            $theme = \App\Models\landlord\SaasSettings::allCached();
        } catch (\Throwable $e) {
            $theme = [];
        }
        $tp = $theme['theme_primary'] ?? '#0066CC';
        $tpl = $theme['theme_primary_light'] ?? '#00A3FF';
        $ta = $theme['theme_accent'] ?? '#66FF33';
        $tl = $theme['theme_lime'] ?? '#8DFF00';
        $td = $theme['theme_dark'] ?? '#003366';
        $tc = $theme['theme_cyan'] ?? '#00D4FF';

        // Derive a hover shade (darken primary by ~15%)
        function hexDarken(string $hex, int $pct = 15): string
        {
            $hex = ltrim($hex, '#');
            [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
            $r = max(0, intval($r * (1 - $pct / 100)));
            $g = max(0, intval($g * (1 - $pct / 100)));
            $b = max(0, intval($b * (1 - $pct / 100)));
            return sprintf('#%02x%02x%02x', $r, $g, $b);
        }
        $tp_hover = hexDarken($tp, 12);
        $siteName = $theme['site_name'] ?? 'NovaxPOS';
        $siteFont = $theme['site_font'] ?? 'Inter';
        $fontSlug = str_replace(' ', '+', $siteFont);
    @endphp
    <link
        href="https://fonts.googleapis.com/css2?family={{ $fontSlug }}:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Syne:wght@700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            /* ── LinkedPOS Brand Theme ── */
            --lp-primary: {{ $tp }};
            --lp-primary-light: {{ $tpl }};
            --lp-primary-hover: {{ $tp_hover }};
            --lp-accent: {{ $ta }};
            --lp-lime: {{ $tl }};
            --lp-dark: {{ $td }};
            --lp-cyan: {{ $tc }};

            /* ── Layout ── */
            --sidebar-bg: {{ $td }};
            --sidebar-width: 260px;
            --topbar-height: 62px;
            --accent: var(--lp-primary);
            --accent-hover: var(--lp-primary-light);
            --body-bg: #f0f4f8;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--body-bg);
            font-family: '{{ $siteFont }}', 'Inter', 'Segoe UI', sans-serif;
            color: #1e293b;
        }

        /* ─── Sidebar ─────────────────────────────────────── */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            overflow-y: auto;
            z-index: 1040;
            transition: transform .25s ease;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            background: rgba(0, 0, 0, .15);
        }

        .sidebar-brand .brand-name {
            font-family: '{{ $siteFont }}', sans-serif;
            font-size: 1.3rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
        }

        .sidebar-brand .brand-name span {
            color: var(--lp-accent);
        }

        .sidebar-brand small {
            color: rgba(255, 255, 255, .4);
            font-size: .68rem;
            margin-top: 3px;
            display: block;
            letter-spacing: .04em;
        }

        .nav-section {
            padding: 1.1rem 1.2rem .35rem;
            font-size: .63rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .3);
        }

        .sidebar-nav {
            list-style: none;
            padding: .5rem 0 0;
            margin: 0;
            flex: 1;
        }

        .sidebar-nav .nav-link {
            color: rgba(255, 255, 255, .6);
            padding: .6rem 1.5rem;
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .85rem;
            font-weight: 500;
            border-radius: 0;
            transition: all .15s;
            text-decoration: none;
            border-left: 3px solid transparent;
        }

        .sidebar-nav .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, .08);
            border-left-color: rgba(255, 255, 255, .3);
        }

        .sidebar-nav .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, .12);
            border-left-color: var(--lp-accent);
            font-weight: 600;
        }

        .sidebar-nav .nav-link.active i {
            color: var(--lp-accent);
        }

        .sidebar-nav .nav-link i {
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            font-size: .9rem;
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, .08);
            font-size: .73rem;
            color: rgba(255, 255, 255, .3);
        }

        /* ─── Topbar ────────────────────────────────────────── */
        #topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: #fff;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            z-index: 1030;
            gap: 1rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
        }

        .topbar-title {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
            flex: 1;
        }

        .topbar-badge {
            display: flex;
            align-items: center;
            gap: .5rem;
            background: rgba(0, 102, 204, .06);
            border: 1px solid rgba(0, 102, 204, .15);
            padding: .35rem .9rem;
            border-radius: 999px;
            font-size: .8rem;
            color: var(--lp-primary);
            font-weight: 500;
        }

        .topbar-badge i {
            font-size: .75rem;
        }

        /* ─── Main Content ──────────────────────────────────── */
        #main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            min-height: 100vh;
        }

        .content-wrapper {
            padding: 1.75rem;
        }

        /* ─── Cards ─────────────────────────────────────────── */
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .07);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            padding: 1rem 1.25rem;
        }

        /* ─── Stat Cards ─────────────────────────────────────── */
        .stat-card {
            border-radius: 14px;
            padding: 1.35rem 1.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            right: -10px;
            top: -10px;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }

        .stat-card .stat-label {
            font-size: .72rem;
            opacity: .75;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            margin-top: .25rem;
        }

        .stat-card .stat-sub {
            font-size: .75rem;
            opacity: .65;
            margin-top: .15rem;
        }

        .stat-card .stat-icon {
            font-size: 2rem;
            opacity: .2;
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .bg-lp-primary {
            background: linear-gradient(135deg, var(--lp-dark), var(--lp-primary)) !important;
        }

        .bg-lp-light {
            background: linear-gradient(135deg, var(--lp-primary), var(--lp-primary-light)) !important;
        }

        .bg-lp-cyan {
            background: linear-gradient(135deg, #0077aa, var(--lp-cyan)) !important;
        }

        .bg-lp-accent {
            background: linear-gradient(135deg, #228800, var(--lp-accent)) !important;
        }

        .bg-lp-warning {
            background: linear-gradient(135deg, #c07000, #f59e0b) !important;
        }

        .bg-lp-danger {
            background: linear-gradient(135deg, #991b1b, #ef4444) !important;
        }

        /* ─── Buttons ────────────────────────────────────────── */
        .btn-lp-primary {
            background: var(--lp-primary);
            color: #fff;
            border: none;
            transition: background .2s, transform .15s;
        }

        .btn-lp-primary:hover {
            background: var(--lp-primary-hover);
            color: #fff;
            transform: translateY(-1px);
        }

        .btn-lp-outline {
            background: transparent;
            color: var(--lp-primary);
            border: 1.5px solid var(--lp-primary);
        }

        .btn-lp-outline:hover {
            background: var(--lp-primary);
            color: #fff;
        }

        /* Override Bootstrap primary buttons to match theme */
        .btn-primary {
            background-color: var(--lp-primary) !important;
            border-color: var(--lp-primary) !important;
        }

        .btn-primary:hover {
            background-color: var(--lp-primary-hover) !important;
            border-color: var(--lp-primary-hover) !important;
        }

        /* ─── Badges ─────────────────────────────────────────── */
        .badge-active {
            background: #dcfce7;
            color: #166534;
            padding: .3em .7em;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: .3em .7em;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
        }

        .badge-trial {
            background: #fef9c3;
            color: #713f12;
            padding: .3em .7em;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
        }

        .badge-pending {
            background: rgba(0, 102, 204, .12);
            color: var(--lp-primary);
            padding: .3em .7em;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
        }

        /* ─── Alerts ─────────────────────────────────────────── */
        .alert {
            border-radius: 10px;
        }

        /* ─── Tables ─────────────────────────────────────────── */
        .table thead th {
            background: #f8fafc;
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody td {
            vertical-align: middle;
            font-size: .875rem;
        }

        .table-hover tbody tr:hover {
            background: rgba(0, 102, 204, .04);
        }

        /* ─── Misc ──── */
        a {
            color: var(--lp-primary);
        }

        a:hover {
            color: var(--lp-primary-light);
        }

        @media(max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.show {
                transform: translateX(0);
            }

            #topbar {
                left: 0;
            }

            #main-content {
                margin-left: 0;
            }
        }
    </style>
    @yield('styles')
    {{-- LIBServices type scale and font, shared with the merchant back office. --}}
    <style>:root { --lp-font: "{{ $siteFont }}"; }</style>
    <link rel="stylesheet" href="{{ url('css/lib-type.css') }}?v={{ @filemtime(public_path('css/lib-type.css')) }}">
</head>

<body>

    <!-- ── Sidebar ──────────────────────────────────────────────── -->
    <nav id="sidebar">
        <div class="sidebar-brand">
            @php $__saasLogo = $theme['site_logo'] ?? null; @endphp
            @if ($__saasLogo)
                @php
                    $__saasLogoUrl = file_exists(public_path('landlord/images/logo/' . $__saasLogo))
                        ? asset('landlord/images/logo/' . $__saasLogo)
                        : route('landlord.logo', $__saasLogo);
                @endphp
                <img src="{{ $__saasLogoUrl }}" alt="{{ $siteName }}"
                    style="max-height:44px;max-width:180px;object-fit:contain;display:block;margin-bottom:6px;">
                <small>Superadmin Portal</small>
            @else
                <div class="brand-name">{{ $siteName }}<span>.</span></div>
                <small>Superadmin Portal</small>
            @endif
        </div>

        <ul class="sidebar-nav">
            <li><span class="nav-section">Main</span></li>
            <li>
                <a href="{{ route('superadmin.dashboard') }}"
                    class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>

            <li><span class="nav-section">Tenants</span></li>
            <li>
                <a href="{{ route('superadmin.clients.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.clients.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Clients
                </a>
            </li>
            <li>
                <a href="{{ route('superadmin.packages.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.packages.*') ? 'active' : '' }}">
                    <i class="fas fa-box-open"></i> Packages
                </a>
            </li>
            <li>
                <a href="{{ route('superadmin.promotions.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.promotions.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i> Promotions
                </a>
            </li>

            <li><span class="nav-section">Finance</span></li>
            <li>
                <a href="{{ route('superadmin.invoices.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.invoices.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i> Invoices
                </a>
            </li>
            <li>
                <a href="{{ route('superadmin.payments.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.payments.*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
            </li>

            <li>
                <a href="{{ route('superadmin.contracts.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.contracts.*') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i> Contracts &amp; SLAs
                </a>
            </li>

            <li><span class="nav-section">Support</span></li>
            <li>
                <a href="{{ route('superadmin.tickets.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt"></i> Tickets
                </a>
            </li>
            <li>
                <a href="{{ route('superadmin.video-tutorials.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.video-tutorials.*') ? 'active' : '' }}">
                    <i class="fas fa-video"></i> Video Tutorials
                </a>
            </li>

            <li><span class="nav-section">Configuration</span></li>
            <li>
                <a href="{{ route('superadmin.settings.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.settings.*') ? 'active' : '' }}">
                    <i class="fas fa-palette"></i> Settings &amp; Theme
                </a>
            </li>

            <li><span class="nav-section">Commerce</span></li>
            <li>
                <a href="{{ route('superadmin.marketplace.index') }}"
                    class="nav-link {{ request()->routeIs('superadmin.marketplace.*') && !request()->routeIs('superadmin.marketplace.reports') ? 'active' : '' }}">
                    <i class="fas fa-store"></i> Marketplace Admin
                </a>
            </li>
            <li>
                <a href="{{ route('superadmin.marketplace.reports') }}"
                    class="nav-link {{ request()->routeIs('superadmin.marketplace.reports') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i> Marketplace Reports
                </a>
            </li>
            <li>
                <a href="{{ route('marketplace.index') }}" class="nav-link" target="_blank">
                    <i class="fas fa-shopping-bag"></i> View Marketplace
                </a>
            </li>

            <li><span class="nav-section">Account</span></li>
            <li>
                <a href="{{ route('landing') }}" class="nav-link" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Site
                </a>
            </li>
            <li>
                <form action="{{ route('superadmin.logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </li>
        </ul>

        <div class="sidebar-footer">
            {{ $siteName }} &copy; {{ date('Y') }}
        </div>
    </nav>

    <!-- ── Topbar ───────────────────────────────────────────────── -->
    <div id="topbar">
        <button class="btn btn-sm btn-light d-md-none me-2"
            onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="fas fa-bars"></i>
        </button>
        <span class="topbar-title">@yield('page-title', 'Dashboard')</span>

        {{-- ── Marketplace notifications bell ── --}}
        @php
            try {
                $saNotifs = \Illuminate\Support\Facades\DB::connection('saleprosaas_landlord')
                    ->table('marketplace_notifications')
                    ->where('recipient_type', 'superadmin')
                    ->whereNull('read_at')
                    ->orderByDesc('id')->limit(10)->get();
            } catch (\Throwable $e) { $saNotifs = collect(); }
        @endphp
        <span style="position:relative;margin-left:auto;margin-right:14px;">
            <a href="#" onclick="document.getElementById('sa-notif-dd').classList.toggle('d-none');return false;"
               style="color:#475569;font-size:1.05rem;position:relative;text-decoration:none;">
                <i class="fas fa-bell"></i>
                @if($saNotifs->count())
                    <span style="position:absolute;top:-7px;right:-9px;background:#ef4444;color:#fff;font-size:.62rem;font-weight:700;border-radius:20px;padding:1px 5px;">{{ $saNotifs->count() }}</span>
                @endif
            </a>
            <div id="sa-notif-dd" class="d-none"
                 style="position:absolute;right:0;top:32px;width:330px;max-height:380px;overflow-y:auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 12px 32px rgba(0,0,0,.12);z-index:1000;">
                @forelse($saNotifs as $n)
                    <a href="{{ route('superadmin.marketplace.orders') ?? '#' }}"
                       onclick="fetch('{{ route('superadmin.marketplace.notifications.read', $n->id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});"
                       style="display:block;padding:10px 14px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:#0f172a;font-size:.82rem;">
                        <strong style="display:block;">{{ $n->title }}</strong>
                        <span style="color:#64748b;font-size:.75rem;">{{ $n->body }}</span>
                        <span style="color:#94a3b8;font-size:.7rem;display:block;">{{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}</span>
                    </a>
                @empty
                    <div style="padding:16px;text-align:center;color:#94a3b8;font-size:.82rem;">No unread notifications</div>
                @endforelse
            </div>
        </span>

        <span class="topbar-badge">
            <i class="fas fa-user-shield"></i>
            {{ Auth::guard('superadmin')->user()->name ?? 'Admin' }}
        </span>
    </div>

    <!-- ── Main Content ─────────────────────────────────────────── -->
    <div id="main-content">
        <div class="content-wrapper">

            @if (session('message'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('not_permitted'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    @stack('scripts')
</div><!-- /#wrapper -->

</body>
</html>
