<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        try { $th = \App\Models\landlord\SaasSettings::allCached(); } catch (\Throwable $e) { $th = []; }
        $tp    = $th['theme_primary']       ?? '#0066CC';
        $tpl   = $th['theme_primary_light'] ?? '#3391FF';
        $ta    = $th['theme_accent']        ?? '#22C55E';
        $td    = $th['theme_dark']          ?? '#001a33';
        $siteN = $th['site_name']           ?? $th['site_title'] ?? 'NovaxPOS';
        $logo  = $th['site_logo']           ?? null;
        $logoUrl = ($logo && file_exists(public_path('landlord/images/logo/'.$logo)))
                    ? asset('landlord/images/logo/'.$logo) : null;
        $retryAfter = null;
        try { $retryAfter = $exception?->getHeaders()['Retry-After'] ?? null; } catch (\Throwable $e) {}
    @endphp
    <title>429 | Too Many Requests — {{ $siteN }}</title>
    <link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">
    <style>
        :root{ --lp-primary:{{ $tp }}; --lp-primary-light:{{ $tpl }}; --lp-accent:{{ $ta }}; --lp-dark:{{ $td }}; }
        *{ box-sizing:border-box; }
        body{ margin:0; min-height:100vh; font-family:'Inter','Segoe UI',sans-serif; color:#0f172a; padding:20px;
              display:flex; align-items:center; justify-content:center;
              background:radial-gradient(circle at 15% 15%, {{ $tpl }}30, transparent 42%), linear-gradient(135deg, {{ $td }}, {{ $tp }}); }
        .err-shell{ width:100%; max-width:520px; text-align:center; }
        .err-card{ position:relative; background:#fff; border-radius:24px; box-shadow:0 24px 70px rgba(0,0,0,.28); padding:3rem 2.5rem 2.5rem; overflow:hidden; }
        .err-watermark{ position:absolute; top:-18px; right:14px; font-size:9rem; font-weight:900; line-height:1; color:var(--lp-primary); opacity:.06; pointer-events:none; }
        .err-brand{ display:flex; align-items:center; justify-content:center; gap:.5rem; margin-bottom:1.5rem; }
        .err-brand img{ max-height:38px; }
        .err-brand .nm{ font-weight:800; font-size:1.05rem; color:var(--lp-dark); }
        .err-brand .nm span{ color:var(--lp-primary); }
        .err-icon{ width:84px; height:84px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 1.25rem;
                   background:#fef3c7; color:#d97706; }
        .err-code{ font-size:.85rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; color:#d97706; margin-bottom:.4rem; }
        .err-title{ font-size:1.5rem; font-weight:800; margin:0 0 .6rem; }
        .err-msg{ font-size:.92rem; color:#64748b; line-height:1.6; margin:0 auto 1.5rem; max-width:400px; }
        .err-actions{ display:flex; gap:.6rem; justify-content:center; flex-wrap:wrap; }
        .btn-x{ border:none; border-radius:12px; padding:.7rem 1.7rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:.5rem; cursor:pointer; transition:.2s; font-size:.9rem; }
        .btn-primary-x{ background:var(--lp-primary); color:#fff; }
        .btn-primary-x:hover{ background:var(--lp-primary-light); }
        .btn-ghost-x{ background:#f1f5f9; color:#334155; }
        .btn-ghost-x:hover{ background:#e2e8f0; }
        .err-note{ margin-top:1rem; font-size:.78rem; color:#94a3b8; }
        .err-foot{ margin-top:1.4rem; color:rgba(255,255,255,.75); font-size:.78rem; }
    </style>
    {{-- LIBServices font and type scale (shared with the back office). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/lib-type.css">
</head>
<body>
<div class="err-shell">
    <div class="err-card">
        <div class="err-watermark">429</div>
        <div class="err-brand">
            @if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $siteN }}">@else<span class="nm">{{ $siteN }}<span>.</span></span>@endif
        </div>
        <div class="err-icon"><i class="fas fa-clock"></i></div>
        <div class="err-code">Error 429</div>
        <h1 class="err-title">Too Many Requests</h1>
        <p class="err-msg">You've sent too many requests in a short period. Please wait a moment and try again.</p>
        <div class="err-actions">
            <a href="javascript:history.back()" class="btn-x btn-primary-x"><i class="fas fa-arrow-left"></i> Go Back</a>
        </div>
        @if($retryAfter)
            <p class="err-note">Try again in {{ $retryAfter }} seconds.</p>
        @endif
    </div>
    <div class="err-foot">&copy; {{ date('Y') }} {{ $siteN }}</div>
</div>
</body>
</html>
