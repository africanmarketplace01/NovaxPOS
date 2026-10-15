<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        try { $th = \App\Models\landlord\SaasSettings::allCached(); } catch (\Throwable $e) { $th = []; }
        $tp    = $th['theme_primary'] ?? '#0066CC';
        $td    = $th['theme_dark']   ?? '#003366';
        $ta    = $th['theme_accent'] ?? '#66FF33';
        $siteN = $th['site_name']    ?? 'NovaxPOS';
        $siteLogo = $th['site_logo'] ?? null;
        $siteLogoUrl = ($siteLogo && file_exists(public_path('landlord/images/logo/' . $siteLogo)))
                       ? asset('landlord/images/logo/' . $siteLogo) : null;
        $reason = $reason ?? 'suspended';
    @endphp
    <title>Account {{ $reason === 'expired' ? 'Expired' : 'Suspended' }} — {{ $siteN }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">
    <style>
        :root { --lp-primary: {{ $tp }}; --lp-dark: {{ $td }}; --lp-accent: {{ $ta }}; }
        body {
            background: linear-gradient(135deg, {{ $td }}, {{ $tp }});
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            font-family:'Segoe UI',sans-serif;
        }
        .sus-card {
            background:#fff; border-radius:20px; box-shadow:0 20px 60px rgba(0,0,0,.25);
            max-width:520px; width:100%; padding:3rem 2.5rem; text-align:center;
        }
        .sus-icon {
            width:80px; height:80px; border-radius:50%; display:flex;
            align-items:center; justify-content:center; margin:0 auto 1.5rem;
            font-size:2rem;
        }
        .sus-icon.expired  { background:#fef2f2; color:#ef4444; }
        .sus-icon.suspended { background:#fef9c3; color:#d97706; }
        .sus-title { font-size:1.5rem; font-weight:800; color:#1e293b; margin-bottom:.5rem; }
        .sus-body  { color:#64748b; font-size:.9rem; line-height:1.6; margin-bottom:1.75rem; }
        .btn-renew {
            background: var(--lp-primary); color:#fff; border:none; border-radius:10px;
            padding:.7rem 2rem; font-weight:700; font-size:.9rem; text-decoration:none;
            display:inline-block; transition:opacity .2s;
        }
        .btn-renew:hover { opacity:.88; color:#fff; }
        .btn-support {
            background:transparent; color: var(--lp-primary); border:2px solid var(--lp-primary);
            border-radius:10px; padding:.65rem 1.5rem; font-weight:600; font-size:.85rem;
            text-decoration:none; display:inline-block; transition:all .2s;
        }
        .btn-support:hover { background: var(--lp-primary); color:#fff; }
        .brand-text { font-size:1.1rem; font-weight:800; color:#1e293b; }
        .brand-text span { color: var(--lp-primary); }
    </style>
    {{-- LIBServices font and type scale (shared with the back office). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/lib-type.css">
</head>
<body>
<div class="sus-card">

    {{-- Brand --}}
    <div class="mb-4">
        @if($siteLogoUrl)
            <img src="{{ $siteLogoUrl }}" alt="{{ $siteN }}"
                 style="max-height:40px;max-width:160px;object-fit:contain;">
        @else
            <div class="brand-text">{{ $siteN }}<span>.</span></div>
        @endif
    </div>

    {{-- Icon --}}
    <div class="sus-icon {{ $reason === 'expired' ? 'expired' : 'suspended' }}">
        <i class="fas {{ $reason === 'expired' ? 'fa-calendar-times' : 'fa-ban' }}"></i>
    </div>

    {{-- Message --}}
    @if($reason === 'expired')
        <div class="sus-title">Subscription Expired</div>
        <div class="sus-body">
            Your {{ $siteN }} subscription expired
            @if(!empty($expiredAt))
                on <strong>{{ \Carbon\Carbon::parse($expiredAt)->format('M j, Y') }}</strong>
            @endif
            and is no longer active. Renew your plan to restore access to your store.
        </div>
        <div class="d-flex flex-column gap-2 align-items-center">
            <a href="{{ route('pricing') }}" class="btn-renew w-100">
                <i class="fas fa-redo me-2"></i>Renew My Plan
            </a>
            @if(!empty($th['support_email']))
            <a href="mailto:{{ $th['support_email'] }}" class="btn-support w-100">
                <i class="fas fa-envelope me-2"></i>Contact Support
            </a>
            @endif
        </div>
    @else
        <div class="sus-title">Account Suspended</div>
        <div class="sus-body">
            Your account has been temporarily suspended. This may be due to a billing issue
            or a violation of our Terms of Service. Please contact support to resolve this.
        </div>
        <div class="d-flex flex-column gap-2 align-items-center">
            @if(!empty($th['support_email']))
            <a href="mailto:{{ $th['support_email'] }}" class="btn-renew w-100">
                <i class="fas fa-headset me-2"></i>Contact Support
            </a>
            @endif
            <a href="{{ route('landing') }}" class="btn-support w-100">
                <i class="fas fa-home me-2"></i>Go to Homepage
            </a>
        </div>
    @endif

    <div class="mt-4 pt-3" style="border-top:1px solid #f1f5f9;font-size:.75rem;color:#94a3b8;">
        Store ID: <strong>{{ $tenantId ?? '—' }}</strong>
    </div>
</div>
</body>
</html>
