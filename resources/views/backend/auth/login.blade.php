<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $general_setting->site_title ?? config('app.name') }} — Sign In</title>

    {{-- Favicon --}}
    @if(!config('database.connections.saleprosaas_landlord'))
        <link rel="icon" type="image/png" href="{{ url('logo', $general_setting->site_logo) }}">
        <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    @else
        <link rel="icon" type="image/png" href="{{ url('../../logo', $general_setting->site_logo) }}">
        <link rel="stylesheet" href="{{ asset('../../vendor/bootstrap/css/bootstrap.min.css') }}">
    @endif

    {{-- Font --}}
    @if($general_setting->font_css)
        {!! $general_setting->font_css !!}
    @elseif(!empty($theme_setting->font_family))
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family={{ urlencode($theme_setting->font_family) }}:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @else
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    @endif

    {{-- Custom auth CSS from general settings --}}
    @if($general_setting->auth_css)
        {!! $general_setting->auth_css !!}
    @endif

    @php
        // ── Resolve theme colors ───────────────────────────────────────────
        $primaryColor    = $theme_setting->theme_color ?? '#7c5cc4';
        $fontFamily      = $theme_setting->font_family  ?? 'Inter';

        // Auth panel background — light mode
        $authBgType      = $theme_setting->auth_background_type ?? 'color';
        $authColor       = $theme_setting->auth_themed_color     ?? $primaryColor;
        $authDarkColor   = $theme_setting->auth_themed_dark_color ?? '#0f172a';
        $authGradient    = $theme_setting->auth_themed_gradient   ?? [];
        $authDarkGrad    = $theme_setting->auth_themed_dark_gradient ?? [];
        $authDeg         = $theme_setting->auth_themed_deg        ?? 135;
        $authDarkDeg     = $theme_setting->auth_themed_dark_deg   ?? 135;
        $authBgImage     = $theme_setting->auth_background_image  ?? null;
        $authDarkBgImage = $theme_setting->auth_dark_background_image ?? null;
        $authBgOpacity   = $theme_setting->auth_background_opacity ?? 0.85;
        $authDarkOpacity = $theme_setting->auth_dark_background_opacity ?? 0.85;

        // Build light CSS background
        if ($authBgType === 'gradient' && count($authGradient) >= 2) {
            $stops = implode(', ', $authGradient);
            $lightBg = "linear-gradient({$authDeg}deg, {$stops})";
        } elseif ($authBgType === 'image' && $authBgImage) {
            $lightBg = "url('" . asset($authBgImage) . "')";
        } else {
            $lightBg = $authColor;
        }

        // Build dark CSS background
        if ($authBgType === 'gradient' && count($authDarkGrad) >= 2) {
            $stops = implode(', ', $authDarkGrad);
            $darkBg = "linear-gradient({$authDarkDeg}deg, {$stops})";
        } elseif ($authBgType === 'image' && $authDarkBgImage) {
            $darkBg = "url('" . asset($authDarkBgImage) . "')";
        } else {
            $darkBg = $authDarkColor;
        }

        // Logo
        $logoLight = $theme_setting->light_logo ?? null;
        $logoDark  = $theme_setting->dark_logo  ?? null;
        $siteLogo  = $general_setting->site_logo ?? null;
    @endphp

    <style>
        /* ── CSS Variables ─────────────────────────────────── */
        :root {
            --primary:        {{ $primaryColor }};
            --primary-hover:  color-mix(in srgb, {{ $primaryColor }} 80%, #000);
            --font-family:    '{{ $fontFamily }}', 'Inter', sans-serif;

            /* Light mode */
            --bg-page:        #f1f5f9;
            --bg-card:        #ffffff;
            --text-main:      #1e293b;
            --text-muted:     #64748b;
            --border:         #e2e8f0;
            --input-bg:       #f8fafc;
            --input-border:   #e2e8f0;
            --input-focus-bg: #ffffff;

            /* Panel */
            --panel-bg-light: {{ $lightBg }};
            --panel-bg-dark:  {{ $darkBg }};
        }

        /* ── Dark Mode Variables ───────────────────────────── */
        body.dark-mode {
            --bg-page:       #0f172a;
            --bg-card:       #1e293b;
            --text-main:     #f1f5f9;
            --text-muted:    #94a3b8;
            --border:        #334155;
            --input-bg:      #0f172a;
            --input-border:  #334155;
            --input-focus-bg:#1e293b;
        }

        /* ── Base ──────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font-family);
            font-size: 14px;
            background-color: var(--bg-page);
            color: var(--text-main);
            min-height: 100vh;
            transition: background-color .3s, color .3s;
        }

        a { color: var(--primary); text-decoration: none; }
        a:hover { opacity: .8; }

        /* ── Layout ────────────────────────────────────────── */
        .auth-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* LEFT — Form panel */
        .auth-form-side {
            flex: 0 0 100%;
            max-width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 24px;
            background-color: var(--bg-page);
            transition: background-color .3s;
        }

        @media (min-width: 992px) {
            .auth-form-side {
                flex: 0 0 45%;
                max-width: 45%;
            }
        }

        .auth-form-inner {
            width: 100%;
            max-width: 400px;
        }

        /* ── Logo / Brand ──────────────────────────────────── */
        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .auth-brand img {
            height: 40px;
            width: auto;
            object-fit: contain;
        }

        .auth-brand-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -.3px;
        }

        /* ── Headings ──────────────────────────────────────── */
        .auth-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
            letter-spacing: -.4px;
        }

        .auth-subtitle {
            font-size: .875rem;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        /* ── Card ──────────────────────────────────────────── */
        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
            transition: background .3s, border-color .3s;
        }

        /* ── Form Controls ─────────────────────────────────── */
        .form-label {
            font-size: .8125rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
            display: block;
        }

        .form-control {
            width: 100%;
            height: 42px;
            padding: 0 14px;
            font-size: .875rem;
            font-family: var(--font-family);
            background-color: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 10px;
            color: var(--text-main);
            transition: border-color .2s, background .2s, box-shadow .2s;
            outline: none;
        }

        .form-control::placeholder { color: var(--text-muted); }

        .form-control:focus {
            background-color: var(--input-focus-bg);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 15%, transparent);
        }

        .input-group { position: relative; }

        .input-group .form-control { padding-right: 44px; }

        .input-group-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0;
            display: flex;
            align-items: center;
        }

        .input-group-toggle:hover { color: var(--primary); }

        .form-group { margin-bottom: 18px; }

        /* ── Error text ────────────────────────────────────── */
        .field-error {
            font-size: .78rem;
            color: #ef4444;
            margin-top: 4px;
        }

        /* ── Remember / Forgot row ─────────────────────────── */
        .auth-options {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 20px;
            font-size: .8125rem;
        }

        .auth-options a { color: var(--primary); font-weight: 500; }

        /* ── Submit Button ─────────────────────────────────── */
        .btn-auth {
            width: 100%;
            height: 44px;
            border-radius: 10px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-size: .9375rem;
            font-weight: 600;
            font-family: var(--font-family);
            cursor: pointer;
            transition: opacity .2s, transform .1s;
            letter-spacing: .2px;
        }

        .btn-auth:hover  { opacity: .9; }
        .btn-auth:active { transform: scale(.98); }

        /* ── Register link ─────────────────────────────────── */
        .auth-register-link {
            text-align: center;
            margin-top: 22px;
            font-size: .8125rem;
            color: var(--text-muted);
        }

        .auth-register-link a { font-weight: 600; color: var(--primary); }

        /* ── Footer ────────────────────────────────────────── */
        .auth-footer {
            margin-top: 28px;
            text-align: center;
            font-size: .78rem;
            color: var(--text-muted);
        }

        /* ── Alerts ────────────────────────────────────────── */
        .auth-alert {
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: .8375rem;
            border: 1px solid transparent;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .auth-alert-danger  { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
        .auth-alert-success { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }

        body.dark-mode .auth-alert-danger  { background:#450a0a; border-color:#7f1d1d; color:#fca5a5; }
        body.dark-mode .auth-alert-success { background:#052e16; border-color:#14532d; color:#86efac; }

        .auth-alert-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            color: inherit;
            opacity: .6;
            font-size: 1rem;
            line-height: 1;
            padding: 0;
            flex-shrink: 0;
        }

        .auth-alert-close:hover { opacity: 1; }

        /* ── RIGHT panel ───────────────────────────────────── */
        .auth-panel-side {
            display: none;
            flex: 1;
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            margin: 12px 12px 12px 0;
        }

        @media (min-width: 992px) {
            .auth-panel-side { display: flex; }
        }

        .auth-panel-bg {
            position: absolute;
            inset: 0;
            background: var(--panel-bg-light);
            background-size: cover;
            background-position: center;
            transition: background .5s;
        }

        body.dark-mode .auth-panel-bg {
            background: var(--panel-bg-dark);
            background-size: cover;
            background-position: center;
        }

        /* Always render a dark scrim so white text is readable on any background */
        .auth-panel-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(0,0,0,.65) 0%,
                rgba(0,0,0,.25) 50%,
                rgba(0,0,0,.10) 100%
            );
            @if($authBgType === 'image')
            background: rgba(0,0,0,{{ $authBgOpacity }});
            @endif
        }
        body.dark-mode .auth-panel-bg::after {
            background: linear-gradient(
                to top,
                rgba(0,0,0,.70) 0%,
                rgba(0,0,0,.30) 50%,
                rgba(0,0,0,.10) 100%
            );
            @if($authBgType === 'image')
            background: rgba(0,0,0,{{ $authDarkOpacity }});
            @endif
        }

        .auth-panel-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 48px;
            width: 100%;
            color: #fff;
        }

        .auth-panel-tagline {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 12px;
            letter-spacing: -.5px;
            text-shadow: 0 2px 12px rgba(0,0,0,.25);
        }

        .auth-panel-desc {
            font-size: .9375rem;
            opacity: .85;
            line-height: 1.6;
            max-width: 360px;
            text-shadow: 0 1px 6px rgba(0,0,0,.2);
        }

        /* ── Carousel ──────────────────────────────────────── */
        .auth-carousel {
            position: relative;
            width: 100%;
        }

        .auth-carousel-slide {
            display: none;
            animation: fadeSlide .5s ease;
        }

        .auth-carousel-slide.active { display: block; }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .carousel-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: rgba(255,255,255,.18);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .carousel-icon svg { width: 28px; height: 28px; stroke: #fff; fill: none; stroke-width: 1.8; }

        .auth-panel-tagline {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 10px;
            letter-spacing: -.4px;
            text-shadow: 0 2px 12px rgba(0,0,0,.25);
        }

        .auth-panel-desc {
            font-size: .9375rem;
            opacity: .85;
            line-height: 1.65;
            max-width: 360px;
            text-shadow: 0 1px 6px rgba(0,0,0,.2);
        }

        /* Dots */
        .auth-panel-dots {
            display: flex;
            gap: 8px;
            margin-top: 28px;
        }

        .auth-panel-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,.35);
            cursor: pointer;
            transition: all .3s;
        }

        .auth-panel-dots span.active {
            background: #fff;
            width: 24px;
            border-radius: 4px;
        }

        /* ── Dark mode toggle ──────────────────────────────── */
        .theme-toggle {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 100;
            background: var(--bg-card);
            border: 1.5px solid var(--border);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            transition: all .2s;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .theme-toggle:hover { border-color: var(--primary); color: var(--primary); }

        button.dropdown-item { display: flex; }
        button svg { margin: 0 10px; width: 20px; }
    </style>
</head>
<body class="{{ $theme === 'dark' ? 'dark-mode' : '' }}">

{{-- Dark mode toggle --}}
<button class="theme-toggle" id="theme-toggle-btn" title="Toggle dark mode">
    <svg id="icon-sun" class="{{ $theme === 'dark' ? 'd-none' : '' }}" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
    </svg>
    <svg id="icon-moon" class="{{ $theme === 'dark' ? '' : 'd-none' }}" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>
    </svg>
</button>

<div class="auth-wrapper">
    {{-- ── LEFT: Form ── --}}
    <div class="auth-form-side">
        <div class="auth-form-inner">

            {{-- Brand --}}
            <div class="auth-brand">
                @php
                    $logoSrc = null;
                    if ($logoLight && file_exists(public_path($logoLight))) {
                        $logoSrc = asset($logoLight);
                    } elseif ($siteLogo && file_exists(public_path('logo/' . $siteLogo))) {
                        $logoSrc = !config('database.connections.saleprosaas_landlord')
                            ? url('logo', $siteLogo)
                            : url('../../logo', $siteLogo);
                    }
                @endphp
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="{{ $general_setting->site_title ?? config('app.name') }}" id="brand-logo">
                @endif
                <span class="auth-brand-name">{{ $general_setting->site_title ?? config('app.name') }}</span>
            </div>

            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to your account to continue</p>

            {{-- Alerts --}}
            @if(session('delete_message'))
                <div class="auth-alert auth-alert-danger" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ session('delete_message') }}
                    <button class="auth-alert-close" onclick="this.parentElement.remove()">&#10005;</button>
                </div>
            @endif
            @if(session('not_permitted'))
                <div class="auth-alert auth-alert-danger" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ session('not_permitted') }}
                    <button class="auth-alert-close" onclick="this.parentElement.remove()">&#10005;</button>
                </div>
            @endif
            @if(session('message'))
                <div class="auth-alert auth-alert-success" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    {!! session('message') !!}
                    <button class="auth-alert-close" onclick="this.parentElement.remove()">&#10005;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="auth-alert auth-alert-danger" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ session('error') }}
                    <button class="auth-alert-close" onclick="this.parentElement.remove()">&#10005;</button>
                </div>
            @endif

            {{-- Login Form --}}
            <div class="auth-card">
                <form method="POST" action="{{ route('login') }}" id="login-form">
                    @csrf

                    <div class="form-group">
                        <label class="form-label" for="name">Username</label>
                        <input type="text" id="name" name="name" class="form-control"
                               placeholder="Enter your username"
                               @if(!env('USER_VERIFIED')) value="admin" @endif
                               autocomplete="username" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control"
                                   placeholder="••••••••"
                                   @if(!env('USER_VERIFIED')) value="admin" @endif
                                   autocomplete="current-password" required>
                            <button type="button" class="input-group-toggle" id="togglePassword" tabindex="-1">
                                <svg id="icon-hidden" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                </svg>
                                <svg id="icon-show" class="d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    @if($general_setting->disable_forgot_password == 0)
                    <div class="auth-options">
                        <a href="{{ route('password.request') }}">Forgot Password?</a>
                    </div>
                    @else
                    <div style="margin-bottom:16px;"></div>
                    @endif

                    <button type="submit" class="btn-auth">Sign In</button>
                </form>
            </div>

            @if($general_setting->disable_signup == 0)
            <div class="auth-register-link register-section">
                Don't have an account?
                <a href="{{ url('register') }}">Register</a>
            </div>
            @endif

            <div class="auth-footer">
                Developed by {{ env('DEVELOPED_BY') }}
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Feature Carousel Panel ── --}}
    <div class="auth-panel-side">
        <div class="auth-panel-bg"></div>
        <div class="auth-panel-content">
            <div class="auth-carousel" id="authCarousel">

                {{-- Slide 1: Sales & POS --}}
                <div class="auth-carousel-slide active">
                    <div class="carousel-icon">
                        <svg viewBox="0 0 24 24"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </div>
                    <p class="auth-panel-tagline">Fast &amp; Flexible<br>Point of Sale</p>
                    <p class="auth-panel-desc">Process sales, manage cash registers, apply discounts, and print receipts — all from one sleek interface built for speed.</p>
                </div>

                {{-- Slide 2: Inventory --}}
                <div class="auth-carousel-slide">
                    <div class="carousel-icon">
                        <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="17"/><line x1="9.5" y1="14.5" x2="14.5" y2="14.5"/></svg>
                    </div>
                    <p class="auth-panel-tagline">Real-Time<br>Inventory Control</p>
                    <p class="auth-panel-desc">Track stock across multiple warehouses, get low-stock alerts, manage purchases, and prevent shrinkage with barcode scanning.</p>
                </div>

                {{-- Slide 3: Reports --}}
                <div class="auth-carousel-slide">
                    <div class="carousel-icon">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    </div>
                    <p class="auth-panel-tagline">Insightful<br>Business Reports</p>
                    <p class="auth-panel-desc">Profit & loss, daily sales, best sellers, customer dues, and more — get the data you need to make confident decisions.</p>
                </div>

                {{-- Slide 4: HRM & Payroll --}}
                <div class="auth-carousel-slide">
                    <div class="carousel-icon">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <p class="auth-panel-tagline">HRM &amp;<br>Payroll Made Easy</p>
                    <p class="auth-panel-desc">Manage employees, shifts, attendance, leaves, and run payroll with automated calculations — all in one place.</p>
                </div>

                {{-- Slide 5: Multi-location --}}
                <div class="auth-carousel-slide">
                    <div class="carousel-icon">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <p class="auth-panel-tagline">Multi-Location<br>Management</p>
                    <p class="auth-panel-desc">Run multiple stores or warehouses from a single account. Transfer stock, track performance per location, and stay in control.</p>
                </div>

            </div>

            {{-- Dots --}}
            <div class="auth-panel-dots" id="carouselDots">
                <span class="active" data-index="0"></span>
                <span data-index="1"></span>
                <span data-index="2"></span>
                <span data-index="3"></span>
                <span data-index="4"></span>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── 1. Theme (dark / light) ─────────────────────────────
    const savedTheme = @json($theme);
    const body       = document.body;
    const sunIcon    = document.getElementById('icon-sun');
    const moonIcon   = document.getElementById('icon-moon');
    const brandLogo  = document.getElementById('brand-logo');

    function applyTheme(mode) {
        if (mode === 'dark') {
            body.classList.add('dark-mode');
            sunIcon.classList.add('d-none');
            moonIcon.classList.remove('d-none');
            // Swap to dark logo if available
            @if($logoDark)
            if (brandLogo) brandLogo.src = '{{ asset($logoDark) }}';
            @endif
        } else {
            body.classList.remove('dark-mode');
            sunIcon.classList.remove('d-none');
            moonIcon.classList.add('d-none');
            // Restore light logo
            @if($logoLight)
            if (brandLogo) brandLogo.src = '{{ asset($logoLight) }}';
            @endif
        }
    }

    applyTheme(savedTheme);

    document.getElementById('theme-toggle-btn').addEventListener('click', function () {
        const isDark = body.classList.contains('dark-mode');
        const next   = isDark ? 'light' : 'dark';
        document.cookie = `theme=${next}; path=/; max-age=${60*60*24*365}`;
        applyTheme(next);
    });

    // ── 2. Password Toggle ──────────────────────────────────
    const toggleBtn   = document.getElementById('togglePassword');
    const passField   = document.getElementById('password');
    const iconHidden  = document.getElementById('icon-hidden');
    const iconShow    = document.getElementById('icon-show');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (passField.type === 'password') {
                passField.type = 'text';
                iconHidden.classList.add('d-none');
                iconShow.classList.remove('d-none');
            } else {
                passField.type = 'password';
                iconHidden.classList.remove('d-none');
                iconShow.classList.add('d-none');
            }
        });
    }

    // ── 3. Auto-dismiss alerts ──────────────────────────────
    document.querySelectorAll('.auth-alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s, max-height .5s, margin .5s, padding .5s';
            el.style.opacity    = '0';
            el.style.maxHeight  = '0';
            el.style.overflow   = 'hidden';
            el.style.padding    = '0';
            el.style.margin     = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 5000);
    });

    // ── 4. Landlord: package user-count check ───────────────
    @if(config('database.connections.saleprosaas_landlord') && !empty($general_setting->package_id))
        const storedMessage = localStorage.getItem('message');
        if (storedMessage) {
            alert(storedMessage);
            localStorage.removeItem('message');
        }

        const numberOfUserAccount = @json($numberOfUserAccount);

        fetch('{{ route("superadmin.packages.fetchData", $general_setting->package_id) }}')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.number_of_user_account > 0 && data.number_of_user_account <= numberOfUserAccount) {
                    const reg = document.querySelector('.register-section');
                    if (reg) reg.classList.add('d-none');
                }
            })
            .catch(function (err) { console.error('Package check failed:', err); });
    @endif

    // ── 5. Demo auto-login via URL params ───────────────────
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('demo') === '1') {
        const env  = urlParams.get('env');
        const page = urlParams.get('page');
        if (env && page) {
            if (env === '.env.ecom' && page === 'ecom_front') {
                window.open('{{ url('/') }}?demo=true', '_blank');
            } else {
                let val = 'admin';
                if (page === 'back_staff')    val = 'staff';
                if (page === 'back_customer') val = 'james';
                const nameInput = document.querySelector("input[name='name']");
                const passInput = document.querySelector("input[name='password']");
                if (nameInput) nameInput.value = val;
                if (passInput) passInput.value = val;
                const form = document.getElementById('login-form');
                if (form) setTimeout(function () { form.submit(); }, 200);
            }
        }
    }

    // ── 6. Demo button click delegation ────────────────────
    function setEnvCookie(val) {
        const d = new Date();
        d.setTime(d.getTime() + 86400000);
        document.cookie = 'env_name=' + val + '; expires=' + d.toUTCString() + '; path=/';
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.demo-btn');
        if (!btn) return;
        e.preventDefault();
        const env  = btn.getAttribute('data-env');
        const page = btn.getAttribute('data-page');
        const href = btn.getAttribute('href');
        setEnvCookie(env);
        if (env === '.env.ecom' && page === 'ecom_front') {
            window.open('{{ url('/') }}?demo=true', '_blank');
        } else {
            let val = 'admin';
            if (page === 'back_staff')    val = 'staff';
            if (page === 'back_customer') val = 'james';
            const nameInput = document.querySelector("input[name='name']");
            const passInput = document.querySelector("input[name='password']");
            if (nameInput) nameInput.value = val;
            if (passInput) passInput.value = val;
            const form = document.getElementById('login-form');
            if (form) {
                if (href) form.action = href;
                form.submit();
            }
        }
    });

    // ── 7. Dropdown (legacy support) ────────────────────────
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-toggle="dropdown"]');
        if (toggle) {
            e.preventDefault();
            const parent = toggle.parentElement;
            const menu   = parent.querySelector('.dropdown-menu');
            const isOpen = parent.classList.contains('show');
            document.querySelectorAll('.dropdown,.dropup').forEach(function (c) {
                c.classList.remove('show');
                const m = c.querySelector('.dropdown-menu');
                const t = c.querySelector('[data-toggle="dropdown"]');
                if (m) m.classList.remove('show');
                if (t) t.setAttribute('aria-expanded', 'false');
            });
            if (!isOpen) {
                parent.classList.add('show');
                if (menu) menu.classList.add('show');
                toggle.setAttribute('aria-expanded', 'true');
            }
        } else if (!e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown,.dropup').forEach(function (c) {
                c.classList.remove('show');
                const m = c.querySelector('.dropdown-menu');
                const t = c.querySelector('[data-toggle="dropdown"]');
                if (m) m.classList.remove('show');
                if (t) t.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // ── 8. Feature Carousel ─────────────────────────────────
    (function () {
        var slides = document.querySelectorAll('.auth-carousel-slide');
        var dots   = document.querySelectorAll('#carouselDots span');
        var current = 0;
        var total   = slides.length;
        var timer;

        function goTo(n) {
            slides[current].classList.remove('active');
            dots[current].classList.remove('active');
            current = (n + total) % total;
            slides[current].classList.add('active');
            dots[current].classList.add('active');
        }

        function next() { goTo(current + 1); }

        function start() { timer = setInterval(next, 4000); }
        function stop()  { clearInterval(timer); }

        // Dot clicks
        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () {
                stop(); goTo(i); start();
            });
        });

        // Pause on hover
        var panel = document.getElementById('authCarousel');
        if (panel) {
            panel.addEventListener('mouseenter', stop);
            panel.addEventListener('mouseleave', start);
        }

        start();
    })();

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown,.dropup').forEach(function (c) {
                c.classList.remove('show');
            });
        }
    });
});
</script>

</body>
</html>
