<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $general_setting->site_title ?? config('app.name') }} — Sign in</title>

    @php
        $logoLight = $theme_setting->light_logo ?? null;
        $siteLogo = $general_setting->site_logo ?? null;
        $logoSrc = null;

        if ($logoLight && file_exists(public_path($logoLight))) {
            $logoSrc = asset($logoLight);
        } elseif ($siteLogo && file_exists(public_path('logo/' . $siteLogo))) {
            $logoSrc = !config('database.connections.saleprosaas_landlord')
                ? url('logo', $siteLogo)
                : url('../../logo', $siteLogo);
        }

        $safeThemeColor = static fn ($value, $fallback) => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
        $primaryColor = $safeThemeColor($theme_setting->theme_color ?? null, '#164EA6');
        $authBackground = $safeThemeColor($theme_setting->auth_themed_color ?? null, '#f3f5f7');
        $authBackgroundType = $theme_setting->auth_background_type ?? 'themed';
        $authGradient = $theme_setting->auth_themed_gradient ?? [];
        $authGradient = is_array($authGradient)
            ? array_values(array_filter($authGradient, fn ($color) => is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)))
            : [];
        $authGradientAngle = max(0, min(360, (int) ($theme_setting->auth_themed_deg ?? 120)));
        $authBackgroundImage = $theme_setting->auth_background_image ?? null;
        $authBackgroundImageUrl = $authBackgroundImage
            && str_starts_with($authBackgroundImage, 'images/theme_settings/')
            && file_exists(public_path($authBackgroundImage))
                ? asset($authBackgroundImage)
                : null;
        $authImageOverlay = max(0, min(1, (float) ($theme_setting->auth_background_opacity ?? 0.75)));
        $fontFamily = $theme_setting->font_family ?? 'Inter';
        if (!is_string($fontFamily) || !preg_match('/^[a-zA-Z0-9 -]{1,80}$/', $fontFamily)) {
            $fontFamily = 'Inter';
        }
    @endphp

    @if($siteLogo)
        <link rel="icon" type="image/png" href="{{ !config('database.connections.saleprosaas_landlord') ? url('logo', $siteLogo) : url('../../logo', $siteLogo) }}">
    @endif
    @if($general_setting->font_css)
        {!! $general_setting->font_css !!}
    @else
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family={{ urlencode($fontFamily) }}:wght@400;500;600;700&display=swap" rel="stylesheet">
    @endif
    @if($general_setting->auth_css)
        {!! $general_setting->auth_css !!}
    @endif

    <style>
        :root {
            color-scheme: light;
            --login-primary: {{ $primaryColor }};
            --login-primary-hover: color-mix(in srgb, {{ $primaryColor }} 84%, #000);
            --login-font: '{{ $fontFamily }}', Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --login-auth-background: {{ $authBackground }};
            --login-text: #202a36;
            --login-muted: #657180;
            --login-line: #dce2e8;
            --login-page: var(--login-auth-background);
            --login-card: #fff;
            --login-input: #fff;
            --login-panel: color-mix(in srgb, var(--login-primary) 5%, white);
        }

        *, *::before, *::after { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            background: var(--login-page);
            color: var(--login-text);
            font-family: var(--login-font);
            font-size: 15px;
            -webkit-font-smoothing: antialiased;
        }
        @if($authBackgroundType === 'themed_gradient' && count($authGradient) > 1)
            body { background-image: linear-gradient({{ $authGradientAngle }}deg, {{ implode(', ', $authGradient) }}); }
        @elseif($authBackgroundType === 'image' && $authBackgroundImageUrl)
            body { background-image: linear-gradient(rgb(255 255 255 / {{ $authImageOverlay }}), rgb(255 255 255 / {{ $authImageOverlay }})), url('{{ $authBackgroundImageUrl }}'); background-position: center; background-size: cover; }
        @elseif($authBackgroundType === 'black-and-white')
            :root { --login-page: #f3f5f7; }
        @endif

        a { color: var(--login-primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        button, input { font: inherit; }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }

        .login-main {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px 24px;
        }

        .login-content { width: min(100%, 400px); }

        .login-brand {
            display: flex;
            min-height: 40px;
            align-items: center;
            gap: 12px;
            margin-bottom: 44px;
        }

        .login-brand img { max-width: 160px; max-height: 42px; object-fit: contain; }
        .login-brand-name { color: var(--login-text); font-size: 1.1rem; font-weight: 650; }

        .login-title { margin: 0; font-size: 1.8rem; font-weight: 650; letter-spacing: -.04em; }
        .login-intro { margin: 8px 0 28px; color: var(--login-muted); line-height: 1.55; }

        .login-card {
            padding: 28px;
            border: 1px solid var(--login-line);
            border-radius: 10px;
            background: var(--login-card);
        }

        .login-alert {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            padding: 12px 14px;
            border: 1px solid #f2c6c6;
            border-radius: 7px;
            background: #fff5f5;
            color: #8f2525;
            line-height: 1.45;
        }
        .login-alert--success { border-color: #b9dfc6; background: #f2fbf4; color: #24653a; }
        .login-alert button { border: 0; background: transparent; color: inherit; cursor: pointer; }

        .login-field { margin-bottom: 18px; }
        .login-label { display: block; margin-bottom: 7px; font-size: .875rem; font-weight: 600; }
        .login-input {
            display: block;
            width: 100%;
            height: 46px;
            padding: 0 12px;
            border: 1px solid #cbd3dc;
            border-radius: 6px;
            outline: 0;
            background: var(--login-input);
            color: var(--login-text);
            transition: border-color .15s, box-shadow .15s;
        }
        .login-input::placeholder { color: #929ca8; }
        .login-input:focus { border-color: var(--login-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--login-primary) 15%, transparent); }
        .login-input[aria-invalid="true"] { border-color: #c43b3b; }
        .login-error { margin: 6px 0 0; color: #a32c2c; font-size: .82rem; }

        .login-password { position: relative; }
        .login-password .login-input { padding-right: 46px; }
        .login-password-toggle {
            position: absolute;
            top: 50%;
            right: 8px;
            display: grid;
            width: 32px;
            height: 32px;
            place-items: center;
            transform: translateY(-50%);
            border: 0;
            border-radius: 5px;
            background: transparent;
            color: var(--login-muted);
            cursor: pointer;
        }
        .login-password-toggle:hover { background: #eef1f4; color: var(--login-text); }
        .login-password-toggle svg { width: 18px; height: 18px; }
        .login-password-toggle .icon-show[hidden],
        .login-password-toggle .icon-hide[hidden] { display: none; }

        .login-options { display: flex; justify-content: flex-end; margin: -2px 0 20px; font-size: .875rem; }
        .login-submit {
            width: 100%;
            min-height: 46px;
            border: 1px solid var(--login-primary);
            border-radius: 6px;
            background: var(--login-primary);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        .login-submit:hover { border-color: var(--login-primary-hover); background: var(--login-primary-hover); }
        .login-submit:focus-visible, .login-password-toggle:focus-visible {
            outline: 3px solid color-mix(in srgb, var(--login-primary) 40%, transparent);
            outline-offset: 2px;
        }

        .login-register { margin: 20px 0 0; color: var(--login-muted); text-align: center; font-size: .9rem; }
        .login-register a { margin-left: 4px; font-weight: 600; }
        .login-footer { margin-top: 32px; color: var(--login-muted); text-align: center; font-size: .8rem; }
        .login-aside { display: none; }

        @media (min-width: 900px) {
            .login-page { grid-template-columns: minmax(480px, 1fr) minmax(380px, .85fr); }
            .login-aside {
                display: flex;
                min-height: 100vh;
                flex-direction: column;
                justify-content: space-between;
                padding: 56px clamp(36px, 6vw, 96px);
                border-left: 1px solid var(--login-line);
                background: var(--login-panel);
            }
            .login-aside-brand { color: var(--login-primary); font-size: .9rem; font-weight: 650; }
            .login-aside-copy { max-width: 440px; padding-bottom: 10vh; }
            .login-aside-copy h2 { margin: 0 0 14px; font-size: clamp(2rem, 3.1vw, 2.8rem); font-weight: 650; letter-spacing: -.045em; line-height: 1.12; }
            .login-aside-copy p { max-width: 390px; margin: 0; color: var(--login-muted); font-size: 1rem; line-height: 1.7; }
            .login-aside-rule { width: 44px; height: 3px; margin-bottom: 24px; background: var(--login-primary); }
            .login-aside-footer { color: var(--login-muted); font-size: .8rem; }
        }

        @media (max-width: 480px) {
            .login-main { padding: 64px 18px 24px; }
            .login-brand { margin-bottom: 34px; }
            .login-card { padding: 22px 18px; }
        }
    </style>
</head>
<body>
    <main class="login-page">
        <section class="login-main" aria-labelledby="login-title">
            <div class="login-content">
                <a class="login-brand" href="{{ url('/') }}" aria-label="{{ $general_setting->site_title ?? config('app.name') }} home">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="">
                    @else
                        <span class="login-brand-name">{{ $general_setting->site_title ?? config('app.name') }}</span>
                    @endif
                </a>

                <h1 class="login-title" id="login-title">Sign in</h1>
                <p class="login-intro">Enter your account details to continue to your business.</p>

                @if(session('delete_message'))
                    <div class="login-alert" role="alert">{{ session('delete_message') }}<button type="button" aria-label="Dismiss" onclick="this.parentElement.remove()">&times;</button></div>
                @endif
                @if(session('not_permitted'))
                    <div class="login-alert" role="alert">{{ session('not_permitted') }}<button type="button" aria-label="Dismiss" onclick="this.parentElement.remove()">&times;</button></div>
                @endif
                @if(session('message'))
                    <div class="login-alert login-alert--success" role="status">{!! session('message') !!}<button type="button" aria-label="Dismiss" onclick="this.parentElement.remove()">&times;</button></div>
                @endif
                @if(session('error'))
                    <div class="login-alert" role="alert">{{ session('error') }}<button type="button" aria-label="Dismiss" onclick="this.parentElement.remove()">&times;</button></div>
                @endif
                @if($errors->has('name') || $errors->has('password'))
                    <div class="login-alert" role="alert">{{ $errors->first('name') ?: $errors->first('password') }}</div>
                @endif

                <div class="login-card">
                    <form method="POST" action="{{ route('login') }}" id="login-form">
                        @csrf

                        <div class="login-field">
                            <label class="login-label" for="name">Username or email</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="login-input"
                                placeholder="Enter your username or email"
                                value="{{ old('name', !env('USER_VERIFIED') ? 'admin' : '') }}"
                                autocomplete="username"
                                aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                                required
                                autofocus>
                            @if($errors->has('name'))
                                <p class="login-error">{{ $errors->first('name') }}</p>
                            @endif
                        </div>

                        <div class="login-field">
                            <label class="login-label" for="password">Password</label>
                            <div class="login-password">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="login-input"
                                    placeholder="Enter your password"
                                    value="{{ !env('USER_VERIFIED') ? 'admin' : '' }}"
                                    autocomplete="current-password"
                                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                    required>
                                <button type="button" class="login-password-toggle" id="togglePassword" aria-label="Show password" aria-pressed="false">
                                    <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.7a2 2 0 0 0 2.7 2.7"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.9 5.2A10.8 10.8 0 0 1 12 5c4.8 0 8.7 3.1 10 7.5a10.8 10.8 0 0 1-3.1 4.8M6.2 6.2A11 11 0 0 0 2 12.5C3.3 16.9 7.2 20 12 20c1.2 0 2.4-.2 3.4-.6"/>
                                    </svg>
                                    <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true" hidden>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            @if($errors->has('password'))
                                <p class="login-error">{{ $errors->first('password') }}</p>
                            @endif
                        </div>

                        @if($general_setting->disable_forgot_password == 0)
                            <div class="login-options">
                                <a href="{{ route('password.request') }}">Forgot password?</a>
                            </div>
                        @endif

                        <button type="submit" class="login-submit">Sign in</button>
                    </form>
                </div>

                @if($general_setting->disable_signup == 0)
                    <p class="login-register register-section">
                        Don't have an account? <a href="{{ url('register') }}">Create an account</a>
                    </p>
                @endif

                @if(env('DEVELOPED_BY'))
                    <p class="login-footer">Developed by {{ env('DEVELOPED_BY') }}</p>
                @endif
            </div>
        </section>

        <aside class="login-aside" aria-label="About {{ $general_setting->site_title ?? config('app.name') }}">
            <div class="login-aside-brand">{{ $general_setting->site_title ?? config('app.name') }}</div>
            <div class="login-aside-copy">
                <div class="login-aside-rule" aria-hidden="true"></div>
                <h2>Your business, in one place.</h2>
                <p>Manage sales, stock, customers, and day-to-day operations from your business account.</p>
            </div>
            <div class="login-aside-footer">Secure sign in for your business account</div>
        </aside>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var passwordToggle = document.getElementById('togglePassword');
            var password = document.getElementById('password');

            passwordToggle.addEventListener('click', function () {
                var showPassword = password.type === 'password';
                password.type = showPassword ? 'text' : 'password';
                passwordToggle.setAttribute('aria-pressed', String(showPassword));
                passwordToggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
                passwordToggle.querySelector('.icon-hide').hidden = showPassword;
                passwordToggle.querySelector('.icon-show').hidden = !showPassword;
            });

            @if(config('database.connections.saleprosaas_landlord') && !empty($general_setting->package_id))
                var storedMessage = localStorage.getItem('message');
                if (storedMessage) {
                    alert(storedMessage);
                    localStorage.removeItem('message');
                }

                var numberOfUserAccount = @json($numberOfUserAccount);
                fetch('{{ route("superadmin.packages.fetchData", $general_setting->package_id) }}')
                    .then(function (response) {
                        if (!response.ok) throw new Error('Unable to check package registration limits.');
                        return response.json();
                    })
                    .then(function (data) {
                        if (data.number_of_user_account > 0 && data.number_of_user_account <= numberOfUserAccount) {
                            var registerLink = document.querySelector('.register-section');
                            if (registerLink) registerLink.hidden = true;
                        }
                    })
                    .catch(function (error) { console.error('Package check failed:', error); });
            @endif

            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('demo') === '1') {
                var envName = urlParams.get('env');
                var page = urlParams.get('page');
                if (envName && page) {
                    if (envName === '.env.ecom' && page === 'ecom_front') {
                        window.open('{{ url('/') }}?demo=true', '_blank');
                    } else {
                        var demoName = page === 'back_staff' ? 'staff' : (page === 'back_customer' ? 'james' : 'admin');
                        document.getElementById('name').value = demoName;
                        password.value = demoName;
                        window.setTimeout(function () { document.getElementById('login-form').submit(); }, 200);
                    }
                }
            }
        });
    </script>
</body>
</html>
