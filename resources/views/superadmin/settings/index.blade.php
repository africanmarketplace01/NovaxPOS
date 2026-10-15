@extends('superadmin.layout.main')
@section('title', 'Settings & Theme')
@section('page-title', 'Settings & Theme')

@section('styles')
<style>
    .settings-nav { display: flex; gap: 4px; border-bottom: 2px solid #e2e8f0; margin-bottom: 1.75rem; }
    .settings-nav .snav-btn {
        padding: .6rem 1.25rem; font-size: .875rem; font-weight: 600; cursor: pointer;
        border: none; background: transparent; color: #64748b; border-bottom: 2px solid transparent;
        margin-bottom: -2px; transition: all .2s; border-radius: 6px 6px 0 0;
    }
    .settings-nav .snav-btn:hover { color: var(--lp-primary); background: rgba(0,102,204,.05); }
    .settings-nav .snav-btn.active { color: var(--lp-primary); border-bottom-color: var(--lp-primary); }

    .tab-pane { display: none; }
    .tab-pane.active { display: block; }

    /* Color pickers */
    .color-row {
        display: flex; align-items: center; gap: 1rem;
        padding: 1rem 0; border-bottom: 1px solid #f1f5f9;
    }
    .color-row:last-child { border-bottom: none; }
    .color-swatch-wrap { position: relative; flex-shrink: 0; }
    .color-swatch {
        width: 48px; height: 48px; border-radius: 10px; border: 2px solid #e2e8f0;
        cursor: pointer; display: block; transition: transform .15s;
    }
    .color-swatch:hover { transform: scale(1.08); }
    .color-input-hidden {
        position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none;
    }
    .color-hex-input {
        width: 110px; font-family: monospace; font-size: .875rem;
        border: 1px solid #e2e8f0; border-radius: 8px; padding: .45rem .75rem;
        color: #1e293b; text-transform: uppercase;
    }
    .color-hex-input:focus { border-color: var(--lp-primary); outline: none; box-shadow: 0 0 0 3px rgba(0,102,204,.1); }
    .color-meta { flex: 1; }
    .color-label { font-weight: 600; font-size: .9rem; color: #1e293b; }
    .color-desc { font-size: .78rem; color: #64748b; margin-top: 2px; }

    /* Live preview */
    #theme-preview {
        border-radius: 14px; overflow: hidden;
        box-shadow: 0 4px 16px rgba(0,0,0,.1); border: 1px solid #e2e8f0;
    }
    .preview-sidebar {
        width: 200px; flex-shrink: 0; padding: 1rem 0;
    }
    .preview-sidebar-brand {
        padding: .75rem 1rem; font-weight: 800; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,.1);
    }
    .preview-nav-link {
        display: flex; align-items: center; gap: .5rem; padding: .45rem 1rem;
        font-size: .78rem; font-weight: 500; color: rgba(255,255,255,.55);
    }
    .preview-nav-link.active { color: #fff; border-left: 3px solid; }
    .preview-body { flex: 1; background: #f0f4f8; padding: 1rem; }
    .preview-stat {
        border-radius: 10px; padding: .75rem 1rem; color: #fff; margin-bottom: .5rem;
        font-size: .78rem; font-weight: 600;
    }
    .preview-topbar {
        background: #fff; height: 40px; display: flex; align-items: center;
        padding: 0 1rem; border-bottom: 1px solid #e2e8f0; font-size: .78rem;
        font-weight: 600; gap: .5rem; color: #1e293b;
    }
    .preview-dot {
        width: 8px; height: 8px; border-radius: 50%;
    }

    /* Font grid */
    .font-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px; margin-bottom: .5rem; }
    .font-card {
        border: 2px solid #e2e8f0; border-radius: 10px; padding: .75rem; cursor: pointer;
        transition: all .2s; background: #fff; position: relative; overflow: hidden;
    }
    .font-card:hover { border-color: var(--lp-primary); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,102,204,.12); }
    .font-card.selected { border-color: var(--lp-primary); background: rgba(0,102,204,.04); }
    .font-card-name { font-size: .95rem; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
    .font-card-sample { font-size: 1.3rem; color: #475569; line-height: 1.1; margin-bottom: 4px; }
    .font-card-desc { font-size: .7rem; color: #94a3b8; line-height: 1.3; }
    .font-card-check { position: absolute; top: 8px; right: 8px; color: var(--lp-primary); font-size: .9rem; }

    /* Preset chips */
    .preset-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 1.5rem; }
    .preset-chip {
        display: flex; align-items: center; gap: 6px; padding: 6px 12px;
        border-radius: 999px; border: 1.5px solid #e2e8f0; cursor: pointer;
        font-size: .8rem; font-weight: 600; color: #475569; transition: all .2s; background: #fff;
    }
    .preset-chip:hover { border-color: var(--lp-primary); color: var(--lp-primary); }
    .preset-chip .dot { width: 12px; height: 12px; border-radius: 50%; }
</style>
@endsection

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-700">Settings &amp; Theme</h5>
        <small class="text-muted">Configure your platform appearance and general options</small>
    </div>
</div>

<!-- Tab nav -->
<div class="settings-nav">
    <button type="button" class="snav-btn active" onclick="showTab('theme', this)">
        <i class="fas fa-palette me-2"></i>Theme &amp; Colors
    </button>
    <button type="button" class="snav-btn" onclick="showTab('typography', this)">
        <i class="fas fa-font me-2"></i>Typography
    </button>
    <button type="button" class="snav-btn" onclick="showTab('general', this)">
        <i class="fas fa-cog me-2"></i>General
    </button>
    <button type="button" class="snav-btn" onclick="showTab('gateways', this)">
        <i class="fas fa-credit-card me-2"></i>Payment Gateways
    </button>
    <button type="button" class="snav-btn" onclick="showTab('email', this)">
        <i class="fas fa-envelope me-2"></i>Email / SMTP
    </button>
    <button type="button" class="snav-btn" onclick="showTab('profile', this)">
        <i class="fas fa-user-shield me-2"></i>My Profile
    </button>
</div>

<form action="{{ route('superadmin.settings.update') }}" method="POST" id="settings-form" enctype="multipart/form-data">
    @csrf @method('PUT')

    <!-- ══ THEME TAB ════════════════════════════════════════════════ -->
    <div class="tab-pane active" id="tab-theme">
        <div class="row g-4">
            <!-- Color pickers -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-palette" style="color:var(--lp-primary)"></i>
                        Brand Colors
                    </div>
                    <div class="card-body">

                        <!-- Preset themes -->
                        <div class="mb-3">
                            <div class="fw-600 mb-2" style="font-size:.8rem;color:#64748b;letter-spacing:.05em;text-transform:uppercase;">Quick presets</div>
                            <div class="preset-chips">
                                {{-- LIBServices design system (BrandManager::DEFAULT_COLORS there):
                                     primary blue, lighter blue, red CTA accent, success green, navy, info blue. --}}
                                <div class="preset-chip" onclick="applyPreset('#164EA6','#598BFC','#D71920','#1B8A5A','#0B2E63','#164EA6')" title="Same palette as LIBServices">
                                    <div class="dot" style="background:linear-gradient(135deg,#164EA6 50%,#D71920 50%)"></div> LIBServices
                                </div>
                                <div class="preset-chip" onclick="applyPreset('#0066CC','#00A3FF','#66FF33','#8DFF00','#003366','#00D4FF')">
                                    <div class="dot" style="background:#0066CC"></div> LinkedPOS (Default)
                                </div>
                                <div class="preset-chip" onclick="applyPreset('#6366f1','#818cf8','#10b981','#34d399','#0f172a','#06b6d4')">
                                    <div class="dot" style="background:#6366f1"></div> Indigo
                                </div>
                                <div class="preset-chip" onclick="applyPreset('#0ea5e9','#38bdf8','#f59e0b','#fbbf24','#0c4a6e','#a78bfa')">
                                    <div class="dot" style="background:#0ea5e9"></div> Sky Blue
                                </div>
                                <div class="preset-chip" onclick="applyPreset('#059669','#34d399','#f59e0b','#fde68a','#064e3b','#06b6d4')">
                                    <div class="dot" style="background:#059669"></div> Emerald
                                </div>
                                <div class="preset-chip" onclick="applyPreset('#dc2626','#f87171','#fbbf24','#fde68a','#7f1d1d','#f97316')">
                                    <div class="dot" style="background:#dc2626"></div> Ruby
                                </div>
                            </div>
                        </div>

                        @php
                        $colors = [
                            ['key'=>'theme_primary',       'label'=>'Primary Color',       'desc'=>'Main brand color — buttons, active links, accents'],
                            ['key'=>'theme_primary_light', 'label'=>'Primary Light',        'desc'=>'Lighter variant — hover states, highlights'],
                            ['key'=>'theme_accent',        'label'=>'Accent / Lime',        'desc'=>'High-contrast accent — active sidebar marker, badges'],
                            ['key'=>'theme_lime',          'label'=>'Lime',                 'desc'=>'Secondary accent for charts and callouts'],
                            ['key'=>'theme_dark',          'label'=>'Dark / Sidebar BG',    'desc'=>'Sidebar background and deep UI backgrounds'],
                            ['key'=>'theme_cyan',          'label'=>'Cyan',                 'desc'=>'Informational highlights and secondary charts'],
                        ];
                        @endphp

                        @foreach($colors as $c)
                        <div class="color-row">
                            <div class="color-swatch-wrap">
                                <div class="color-swatch" id="swatch_{{ $c['key'] }}"
                                     style="background:{{ $settings[$c['key']] ?? '#0066CC' }}"
                                     onclick="document.getElementById('picker_{{ $c['key'] }}').click()"></div>
                                <input type="color" class="color-input-hidden" id="picker_{{ $c['key'] }}"
                                       value="{{ $settings[$c['key']] ?? '#0066CC' }}"
                                       oninput="syncColor('{{ $c['key'] }}', this.value)">
                            </div>
                            <div class="color-meta">
                                <div class="color-label">{{ $c['label'] }}</div>
                                <div class="color-desc">{{ $c['desc'] }}</div>
                            </div>
                            <input type="text" class="color-hex-input" id="hex_{{ $c['key'] }}" name="{{ $c['key'] }}"
                                   value="{{ $settings[$c['key']] ?? '#0066CC' }}" maxlength="7"
                                   oninput="syncHex('{{ $c['key'] }}', this.value)">
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Live preview -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-eye" style="color:var(--lp-primary)"></i>
                        Live Preview
                        <span class="ms-auto badge" style="background:rgba(0,102,204,.1);color:var(--lp-primary);font-size:.7rem;">Updates in real-time</span>
                    </div>
                    <div class="card-body p-3">
                        <div id="theme-preview">
                            <!-- Preview topbar -->
                            <div class="preview-topbar" id="prev-topbar">
                                <div class="preview-dot" id="prev-dot" style="background:var(--lp-accent)"></div>
                                NovaxPOS Admin
                                <span class="ms-auto" style="font-size:.7rem;color:#64748b;">Super Admin</span>
                            </div>
                            <div class="d-flex">
                                <!-- Preview sidebar -->
                                <div class="preview-sidebar" id="prev-sidebar" style="background:var(--lp-dark)">
                                    <div class="preview-sidebar-brand" id="prev-brand" style="color:#fff">
                                        NovaxPOS<span id="prev-accent-dot" style="color:var(--lp-accent)">.</span>
                                    </div>
                                    <div class="preview-nav-link active" id="prev-nav-active"
                                         style="border-left-color:var(--lp-accent)">
                                        <i class="fas fa-chart-line" style="font-size:.7rem"></i> Dashboard
                                    </div>
                                    <div class="preview-nav-link">
                                        <i class="fas fa-users" style="font-size:.7rem"></i> Clients
                                    </div>
                                    <div class="preview-nav-link">
                                        <i class="fas fa-box-open" style="font-size:.7rem"></i> Packages
                                    </div>
                                    <div class="preview-nav-link">
                                        <i class="fas fa-palette" style="font-size:.7rem"></i> Settings
                                    </div>
                                </div>
                                <!-- Preview body -->
                                <div class="preview-body">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="preview-stat" id="prev-stat1"
                                                 style="background:linear-gradient(135deg,var(--lp-dark),var(--lp-primary))">
                                                <div style="font-size:.65rem;opacity:.7">Clients</div>
                                                <div style="font-size:1.1rem;font-weight:700">142</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="preview-stat" id="prev-stat2"
                                                 style="background:linear-gradient(135deg,var(--lp-primary),var(--lp-primary-light))">
                                                <div style="font-size:.65rem;opacity:.7">Revenue</div>
                                                <div style="font-size:1.1rem;font-weight:700">$9,280</div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div style="background:#fff;border-radius:8px;padding:.75rem;font-size:.75rem;">
                                                <div style="font-weight:600;margin-bottom:.5rem;color:#1e293b">Recent Clients</div>
                                                <div style="display:flex;align-items:center;justify-content:space-between;padding:.3rem 0;border-bottom:1px solid #f1f5f9">
                                                    <span>Acme Corp</span>
                                                    <span class="badge-active" style="font-size:.65rem;padding:.2em .5em;border-radius:999px;background:#dcfce7;color:#166534">Active</span>
                                                </div>
                                                <div style="display:flex;align-items:center;justify-content:space-between;padding:.3rem 0">
                                                    <span>Beta LLC</span>
                                                    <span style="font-size:.65rem;padding:.2em .5em;border-radius:999px;"
                                                          id="prev-badge" class="badge-pending">Trial</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm w-100 mt-2" id="prev-btn"
                                            style="background:var(--lp-primary);color:#fff;border:none;border-radius:8px;font-size:.75rem;padding:.45rem">
                                        <i class="fas fa-plus me-1"></i> Add Client
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Landing page color chips -->
                        <div class="mt-3">
                            <div class="fw-600 mb-2" style="font-size:.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em">Landing page preview</div>
                            <div style="background:#003366;border-radius:10px;padding:1rem;display:flex;align-items:center;gap:.75rem;" id="prev-landing">
                                <div style="flex:1">
                                    <div style="font-size:.7rem;color:rgba(255,255,255,.5);margin-bottom:2px">Hero button</div>
                                    <button class="btn btn-sm" id="prev-landing-btn"
                                            style="background:var(--lp-primary);color:#fff;border:none;border-radius:6px;font-size:.75rem">
                                        Start free trial →
                                    </button>
                                </div>
                                <div style="flex:1">
                                    <div style="font-size:.7rem;color:rgba(255,255,255,.5);margin-bottom:2px">Accent chip</div>
                                    <span id="prev-chip"
                                          style="display:inline-block;background:rgba(102,255,51,.15);color:var(--lp-accent);padding:.25em .75em;border-radius:999px;font-size:.75rem;border:1px solid var(--lp-accent)">
                                        ⚡ AI Analytics
                                    </span>
                                </div>
                                <div style="flex:1">
                                    <div style="font-size:.7rem;color:rgba(255,255,255,.5);margin-bottom:2px">Gradient bar</div>
                                    <div style="height:12px;border-radius:6px;" id="prev-grad"
                                         style="background:linear-gradient(90deg,var(--lp-dark),var(--lp-primary),var(--lp-cyan))"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ TYPOGRAPHY TAB ════════════════════════════════════════════ -->
    <div class="tab-pane" id="tab-typography">
        @php
        $availableFonts = [
            'Inter'            => ['category' => 'Sans-serif', 'desc' => 'Clean modern UI font — used by default'],
            'Poppins'          => ['category' => 'Sans-serif', 'desc' => 'Geometric, friendly, great for SaaS UIs'],
            'Roboto'           => ['category' => 'Sans-serif', 'desc' => 'Google\'s standard material design font'],
            'Open Sans'        => ['category' => 'Sans-serif', 'desc' => 'Highly legible, widely used on the web'],
            'Lato'             => ['category' => 'Sans-serif', 'desc' => 'Warm & professional, great readability'],
            'Montserrat'       => ['category' => 'Sans-serif', 'desc' => 'Strong, modern, often used for headings'],
            'Raleway'          => ['category' => 'Sans-serif', 'desc' => 'Elegant, thin strokes, premium feel'],
            'Nunito'           => ['category' => 'Sans-serif', 'desc' => 'Rounded, approachable, great for dashboards'],
            'DM Sans'          => ['category' => 'Sans-serif', 'desc' => 'Low-contrast, optical-size optimised'],
            'Outfit'           => ['category' => 'Sans-serif', 'desc' => 'Contemporary, minimal, startup-friendly'],
            'Plus Jakarta Sans'=> ['category' => 'Sans-serif', 'desc' => 'Technical, confident, modern SaaS choice'],
            'Ubuntu'           => ['category' => 'Sans-serif', 'desc' => 'Humanist, desktop-ready, distinctive'],
            'Source Sans 3'    => ['category' => 'Sans-serif', 'desc' => 'Adobe\'s open-source UI workhorse'],
            'Noto Sans'        => ['category' => 'Sans-serif', 'desc' => 'Multilingual-first, neutral, wide support'],
            'Playfair Display' => ['category' => 'Serif',      'desc' => 'Elegant editorial — for premium brands'],
            'Merriweather'     => ['category' => 'Serif',      'desc' => 'Print-quality serif for content-heavy UIs'],
            'PT Serif'         => ['category' => 'Serif',      'desc' => 'Classic Russian-origin serif, balanced'],
            'IBM Plex Sans'    => ['category' => 'Sans-serif', 'desc' => 'IBM\'s technical, no-nonsense font family'],
            'Figtree'          => ['category' => 'Sans-serif', 'desc' => 'Fresh geometric sans, very modern feel'],
            'Lexend'           => ['category' => 'Sans-serif', 'desc' => 'Designed to reduce visual stress in reading'],
        ];
        $currentFont = $settings['site_font'] ?? 'Inter';
        @endphp

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-font" style="color:var(--lp-primary)"></i>
                        System Font
                        <span class="ms-auto badge" style="background:rgba(0,102,204,.1);color:var(--lp-primary);font-size:.7rem;">Applied sitewide</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted" style="font-size:.85rem;margin-bottom:1rem;">
                            The selected font applies to the landing page, superadmin panel, and all tenant POS dashboards.
                            Fonts are loaded from Google Fonts.
                        </p>

                        <input type="hidden" name="site_font" id="selected_font_input" value="{{ $currentFont }}">

                        @php $lastCategory = ''; @endphp
                        @foreach($availableFonts as $fontName => $meta)
                            @if($meta['category'] !== $lastCategory)
                                @if($lastCategory !== '') </div> @endif
                                <div class="fw-600 mb-2 mt-3" style="font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">
                                    {{ $meta['category'] }}
                                </div>
                                <div class="font-grid">
                                @php $lastCategory = $meta['category']; @endphp
                            @endif

                            @php $fontSlug = str_replace(' ', '+', $fontName); @endphp
                            <div class="font-card {{ $currentFont === $fontName ? 'selected' : '' }}"
                                 data-font="{{ $fontName }}" data-slug="{{ $fontSlug }}"
                                 onclick="selectFont('{{ $fontName }}', '{{ $fontSlug }}')">
                                <div class="font-card-name" style="font-family:'{{ $fontName }}',sans-serif">
                                    {{ $fontName }}
                                </div>
                                <div class="font-card-sample" id="sample_{{ Str::slug($fontName) }}"
                                     style="font-family:'{{ $fontName }}',sans-serif">
                                    Aa Bb Cc 123
                                </div>
                                <div class="font-card-desc">{{ $meta['desc'] }}</div>
                                @if($currentFont === $fontName)
                                <div class="font-card-check"><i class="fas fa-check-circle"></i></div>
                                @endif
                            </div>
                        @endforeach
                        </div>{{-- close last font-grid --}}
                    </div>
                </div>
            </div>

            <!-- Live preview -->
            <div class="col-lg-5">
                <div class="card" style="position:sticky;top:80px">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-eye" style="color:var(--lp-primary)"></i>
                        Font Preview
                    </div>
                    <div class="card-body" id="font-preview-body">
                        <div style="margin-bottom:1rem;">
                            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:4px">Currently selected</div>
                            <div id="preview-font-name" style="font-size:1.2rem;font-weight:700;color:var(--lp-primary)">{{ $currentFont }}</div>
                        </div>
                        <div id="preview-headline" style="font-size:1.7rem;font-weight:700;line-height:1.2;margin-bottom:.5rem;color:#1e293b">
                            The Modern Cloud POS
                        </div>
                        <div id="preview-body-text" style="font-size:.9rem;color:#475569;line-height:1.6;margin-bottom:1rem">
                            Manage your sales, inventory, and analytics from one powerful dashboard.
                            Built for speed, designed for scale.
                        </div>
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem">
                            <button id="preview-btn" class="btn btn-sm" style="background:var(--lp-primary);color:#fff;border:none;border-radius:6px;font-size:.82rem;padding:.4rem 1rem">
                                Start Free Trial
                            </button>
                            <button class="btn btn-sm" style="border:1px solid var(--lp-primary);color:var(--lp-primary);background:transparent;border-radius:6px;font-size:.82rem;padding:.4rem 1rem">
                                Learn More
                            </button>
                        </div>
                        <div style="font-size:.75rem;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:.75rem;">
                            <i class="fas fa-info-circle me-1"></i>
                            Preview loads the font from Google Fonts.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ GENERAL TAB ════════════════════════════════════════════ -->
    <div class="tab-pane" id="tab-general">
        <div class="row g-4">

            <div class="col-lg-7">
                {{-- Site Details --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-globe" style="color:var(--lp-primary)"></i>
                        Site Details
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Site Name <span class="text-danger">*</span></label>
                            <input type="text" name="site_name" class="form-control"
                                   value="{{ old('site_name', $settings['site_name'] ?? 'NovaxPOS') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tagline / Slogan</label>
                            <input type="text" name="site_tagline" class="form-control"
                                   value="{{ old('site_tagline', $settings['site_tagline'] ?? '') }}"
                                   placeholder="The modern cloud POS for every business">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Marketplace Name</label>
                            <input type="text" name="marketplace_name" class="form-control" maxlength="80"
                                   value="{{ old('marketplace_name', $settings['marketplace_name'] ?? 'NovaxSuite POS & Marketplace') }}"
                                   placeholder="NovaxSuite POS & Marketplace">
                            <div class="form-text">Shown to shoppers across the online marketplace (header, page titles, footer).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Support Email</label>
                            <input type="email" name="support_email" class="form-control"
                                   value="{{ old('support_email', $settings['support_email'] ?? '') }}"
                                   placeholder="support@yourdomain.com">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Support Phone</label>
                            <input type="text" name="support_phone" class="form-control"
                                   value="{{ old('support_phone', $settings['support_phone'] ?? '') }}"
                                   placeholder="+1 (800) 000-0000">
                        </div>
                    </div>
                </div>

                {{-- Subscription Defaults --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-calendar-alt" style="color:var(--lp-primary)"></i>
                        Subscription Defaults
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Default Trial Days</label>
                                <input type="number" name="trial_days" class="form-control"
                                       value="{{ old('trial_days', $settings['trial_days'] ?? 14) }}"
                                       min="0" max="365">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Grace Period (days)</label>
                                <input type="number" name="grace_days" class="form-control"
                                       value="{{ old('grace_days', $settings['grace_days'] ?? 3) }}"
                                       min="0" max="30">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Yearly Billing Badge
                                    <small class="text-muted fw-normal ms-1">— shown on the "Yearly" toggle button on the landing page</small>
                                </label>
                                <input type="text" name="yearly_savings_label" class="form-control"
                                       value="{{ old('yearly_savings_label', $settings['yearly_savings_label'] ?? '') }}"
                                       placeholder="e.g. Save 20%  ·  2 months free  ·  Best value"
                                       maxlength="30">
                                <div class="form-text">Leave blank to show no badge. This is purely a label — it does not calculate or enforce any discount.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Currency</label>
                                <select name="currency" class="form-select">
                                    @php
                                    $currencies = ['USD'=>'USD — US Dollar','EUR'=>'EUR — Euro','GBP'=>'GBP — British Pound',
                                                   'CAD'=>'CAD — Canadian Dollar','AUD'=>'AUD — Australian Dollar',
                                                   'INR'=>'INR — Indian Rupee','NGN'=>'NGN — Nigerian Naira',
                                                   'GHS'=>'GHS — Ghanaian Cedi','KES'=>'KES — Kenyan Shilling'];
                                    $cur = $settings['currency'] ?? 'USD';
                                    @endphp
                                    @foreach($currencies as $code => $label)
                                        <option value="{{ $code }}" {{ $cur === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                {{-- Platform Logo --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-image" style="color:var(--lp-primary)"></i>
                        Platform Logo
                        <span class="ms-auto badge" style="background:rgba(0,102,204,.1);color:var(--lp-primary);font-size:.7rem;">PNG / JPG / SVG / WebP · max 2 MB</span>
                    </div>
                    <div class="card-body">
                        @php
                            $currentLogo = $settings['site_logo'] ?? null;
                            $currentLogoUrl = $currentLogo
                                ? (file_exists(public_path('landlord/images/logo/' . $currentLogo))
                                    ? asset('landlord/images/logo/' . $currentLogo)
                                    : route('landlord.logo', $currentLogo))
                                : null;
                        @endphp
                        @if($currentLogo)
                            <div class="text-center mb-3">
                                <div style="background:#f8fafc;border-radius:10px;padding:1.25rem;border:1px solid #e2e8f0;display:inline-block;">
                                    <img src="{{ $currentLogoUrl }}"
                                         alt="Current Logo"
                                         style="max-height:80px;max-width:220px;object-fit:contain;display:block;">
                                </div>
                                <div class="mt-2" style="font-size:.78rem;color:#64748b;">Current logo</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 mb-3"
                                    onclick="if(confirm('Remove the current logo?')) document.getElementById('remove-logo-form').submit()">
                                <i class="fas fa-trash-alt me-1"></i>Remove Logo
                            </button>
                        @else
                            <div id="logo-placeholder" style="background:#f8fafc;border:2px dashed #cbd5e1;border-radius:10px;
                                                               padding:1.5rem;text-align:center;margin-bottom:1rem;cursor:pointer;"
                                 onclick="document.getElementById('site_logo_input').click()">
                                <img id="logo-preview-img" src="" alt=""
                                     style="max-height:80px;max-width:220px;object-fit:contain;display:none;margin:0 auto 8px;">
                                <div id="logo-placeholder-text">
                                    <i class="fas fa-cloud-upload-alt" style="font-size:1.5rem;color:#94a3b8;margin-bottom:.5rem;display:block;"></i>
                                    <span style="font-size:.82rem;color:#94a3b8;">Click to upload or drag &amp; drop</span>
                                </div>
                            </div>
                        @endif

                        <div class="mb-0">
                            <label class="form-label" style="font-size:.82rem;">Upload New Logo</label>
                            <input type="file" name="site_logo" id="site_logo_input" class="form-control form-control-sm"
                                   accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                                   onchange="previewLogo(this)">
                            <div class="form-text">Recommended: transparent PNG, min 200×60 px.</div>
                        </div>
                    </div>
                </div>

                {{-- System Info --}}
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-server" style="color:var(--lp-primary)"></i>
                        System Info
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0" style="font-size:.82rem;">
                            <tbody>
                                <tr>
                                    <td class="ps-3 py-2 text-muted">PHP Version</td>
                                    <td class="pe-3 py-2 text-end fw-semibold">{{ PHP_VERSION }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 py-2 text-muted">Laravel Version</td>
                                    <td class="pe-3 py-2 text-end fw-semibold">{{ app()->version() }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 py-2 text-muted">Environment</td>
                                    <td class="pe-3 py-2 text-end fw-semibold">{{ app()->environment() }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 py-2 text-muted">Cache Driver</td>
                                    <td class="pe-3 py-2 text-end fw-semibold">{{ config('cache.default') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 py-2 text-muted">Queue Driver</td>
                                    <td class="pe-3 py-2 text-end fw-semibold">{{ config('queue.default') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PAYMENT GATEWAYS TAB ════════════════════════════════════ -->
    <div class="tab-pane" id="tab-gateways">
        <div class="row g-4">
            {{-- Stripe --}}
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fab fa-stripe" style="color:#635bff;font-size:1.2rem;"></i>
                        Stripe
                        <span class="ms-auto badge" style="background:rgba(99,91,255,.1);color:#635bff;font-size:.7rem;">Recommended</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:.82rem;">
                            Accept credit/debit cards globally via Stripe. Add your API keys from the
                            <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a>.
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Publishable Key</label>
                            <input type="text" name="stripe_key" class="form-control font-monospace"
                                   value="{{ old('stripe_key', $settings['stripe_key'] ?? '') }}"
                                   placeholder="pk_live_...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secret Key</label>
                            <input type="password" name="stripe_secret" class="form-control font-monospace"
                                   value="{{ old('stripe_secret', $settings['stripe_secret'] ?? '') }}"
                                   placeholder="sk_live_...">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Webhook Secret</label>
                            <input type="password" name="stripe_webhook_secret" class="form-control font-monospace"
                                   value="{{ old('stripe_webhook_secret', $settings['stripe_webhook_secret'] ?? '') }}"
                                   placeholder="whsec_...">
                            <div class="form-text">Set the webhook endpoint: <code>{{ url('/checkout/stripe/webhook') }}</code></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Paystack --}}
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-bolt" style="color:#00c3f7;"></i>
                        Paystack
                        <span class="ms-auto badge" style="background:rgba(0,195,247,.1);color:#00c3f7;font-size:.7rem;">Africa / Global</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:.82rem;">
                            Accept payments via Paystack — great for Africa-first businesses.
                            Get keys from <a href="https://dashboard.paystack.com/#/settings/developer" target="_blank">Paystack Dashboard</a>.
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Public Key</label>
                            <input type="text" name="paystack_public_key" class="form-control font-monospace"
                                   value="{{ old('paystack_public_key', $settings['paystack_public_key'] ?? '') }}"
                                   placeholder="pk_live_...">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Secret Key</label>
                            <input type="password" name="paystack_secret_key" class="form-control font-monospace"
                                   value="{{ old('paystack_secret_key', $settings['paystack_secret_key'] ?? '') }}"
                                   placeholder="sk_live_...">
                            <div class="form-text">Callback URL: <code>{{ url('/checkout/paystack/callback') }}</code></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bank Transfer --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-university" style="color:#16a34a;"></i>
                        Bank Transfer / Manual Payment
                        <div class="ms-auto form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="bank_transfer_enabled" value="1"
                                   id="bank_transfer_enabled" {{ !empty($settings['bank_transfer_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="bank_transfer_enabled" style="font-size:.8rem;">Enable</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3" style="font-size:.82rem;">
                            When enabled, tenants will see a "Bank Transfer" option at checkout. They submit their details and payment proof, and you manually activate their account.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control"
                                       value="{{ old('bank_name', $settings['bank_name'] ?? '') }}"
                                       placeholder="e.g. Liberia Bank for Development">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="bank_account_name" class="form-control"
                                       value="{{ old('bank_account_name', $settings['bank_account_name'] ?? '') }}"
                                       placeholder="e.g. BridgeNovax Technologies">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="bank_account_number" class="form-control"
                                       value="{{ old('bank_account_number', $settings['bank_account_number'] ?? '') }}"
                                       placeholder="0123456789">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Routing / Sort Code <small class="text-muted">(optional)</small></label>
                                <input type="text" name="bank_routing_number" class="form-control"
                                       value="{{ old('bank_routing_number', $settings['bank_routing_number'] ?? '') }}"
                                       placeholder="e.g. 021000021">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SWIFT / BIC Code <small class="text-muted">(optional)</small></label>
                                <input type="text" name="bank_swift_code" class="form-control"
                                       value="{{ old('bank_swift_code', $settings['bank_swift_code'] ?? '') }}"
                                       placeholder="e.g. BOFAUS3N">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Additional Payment Instructions <small class="text-muted">(shown at checkout)</small></label>
                                <textarea name="bank_payment_instructions" class="form-control" rows="3"
                                          placeholder="e.g. Transfer the exact amount and use your store name as reference. Send proof to payments@yourdomain.com.">{{ old('bank_payment_instructions', $settings['bank_payment_instructions'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MTN Mobile Money --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span style="background:#FFCB00;color:#000;font-weight:800;font-size:.75rem;padding:2px 8px;border-radius:4px;">MTN</span>
                        MTN Mobile Money
                        <div class="ms-auto form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="mtn_enabled" value="1"
                                   id="mtn_enabled" {{ !empty($settings['mtn_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="mtn_enabled" style="font-size:.8rem;">Enable</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">MTN MoMo Number</label>
                            <input type="text" name="mtn_number" class="form-control"
                                   value="{{ old('mtn_number', $settings['mtn_number'] ?? '') }}"
                                   placeholder="e.g. +231 77 000 0000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Name</label>
                            <input type="text" name="mtn_account_name" class="form-control"
                                   value="{{ old('mtn_account_name', $settings['mtn_account_name'] ?? '') }}"
                                   placeholder="e.g. BridgeNovax Technologies">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Payment Instructions <small class="text-muted">(shown at checkout)</small></label>
                            <textarea name="mtn_instructions" class="form-control" rows="2"
                                      placeholder="e.g. Send payment to the number above and use your store name as reference.">{{ old('mtn_instructions', $settings['mtn_instructions'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Orange Money --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span style="background:#FF6600;color:#fff;font-weight:800;font-size:.75rem;padding:2px 8px;border-radius:4px;">Orange</span>
                        Orange Money
                        <div class="ms-auto form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" name="orange_enabled" value="1"
                                   id="orange_enabled" {{ !empty($settings['orange_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="orange_enabled" style="font-size:.8rem;">Enable</label>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Orange Money Number</label>
                            <input type="text" name="orange_number" class="form-control"
                                   value="{{ old('orange_number', $settings['orange_number'] ?? '') }}"
                                   placeholder="e.g. +231 55 000 0000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Name</label>
                            <input type="text" name="orange_account_name" class="form-control"
                                   value="{{ old('orange_account_name', $settings['orange_account_name'] ?? '') }}"
                                   placeholder="e.g. BridgeNovax Technologies">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Payment Instructions <small class="text-muted">(shown at checkout)</small></label>
                            <textarea name="orange_instructions" class="form-control" rows="2"
                                      placeholder="e.g. Send payment to the number above and use your store name as reference.">{{ old('orange_instructions', $settings['orange_instructions'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gateway status summary --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle" style="color:var(--lp-primary)"></i>
                        Gateway Status
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                    <i class="fab fa-stripe" style="color:#635bff;font-size:1.2rem;"></i>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.85rem;">Stripe</div>
                                        @if(!empty($settings['stripe_key']))
                                            <span class="badge bg-success" style="font-size:.7rem;">Configured</span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size:.7rem;">Not configured</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                    <i class="fas fa-bolt" style="color:#00c3f7;"></i>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.85rem;">Paystack</div>
                                        @if(!empty($settings['paystack_public_key']))
                                            <span class="badge bg-success" style="font-size:.7rem;">Configured</span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size:.7rem;">Not configured</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                    <i class="fas fa-university" style="color:#16a34a;font-size:1.1rem;"></i>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.85rem;">Bank Transfer</div>
                                        @if(!empty($settings['bank_transfer_enabled']))
                                            <span class="badge bg-success" style="font-size:.7rem;">Enabled</span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size:.7rem;">Disabled</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                    <span style="background:#FFCB00;color:#000;font-weight:900;font-size:.65rem;padding:1px 5px;border-radius:3px;flex-shrink:0;">MTN</span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.85rem;">MTN Mobile Money</div>
                                        @if(!empty($settings['mtn_enabled']))
                                            <span class="badge bg-success" style="font-size:.7rem;">Enabled</span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size:.7rem;">Disabled</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-2 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                    <span style="background:#FF6600;color:#fff;font-weight:900;font-size:.65rem;padding:1px 5px;border-radius:3px;flex-shrink:0;">OM</span>
                                    <div>
                                        <div class="fw-semibold" style="font-size:.85rem;">Orange Money</div>
                                        @if(!empty($settings['orange_enabled']))
                                            <span class="badge bg-success" style="font-size:.7rem;">Enabled</span>
                                        @else
                                            <span class="badge bg-secondary" style="font-size:.7rem;">Disabled</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ EMAIL / SMTP TAB ═════════════════════════════════════════ -->
    <div class="tab-pane" id="tab-email">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-paper-plane" style="color:var(--lp-primary)"></i>
                        SMTP Configuration
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control"
                                       value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}"
                                       placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Port</label>
                                <input type="number" name="smtp_port" class="form-control"
                                       value="{{ old('smtp_port', $settings['smtp_port'] ?? '587') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" name="smtp_username" class="form-control"
                                       value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}"
                                       placeholder="your@email.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password / App Password</label>
                                <input type="password" name="smtp_password" class="form-control"
                                       value="{{ old('smtp_password', $settings['smtp_password'] ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    @foreach(['tls','ssl','none'] as $enc)
                                    <option value="{{ $enc }}" {{ ($settings['smtp_encryption'] ?? 'tls') === $enc ? 'selected' : '' }}>
                                        {{ strtoupper($enc) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">From Name</label>
                                <input type="text" name="mail_from_name" class="form-control"
                                       value="{{ old('mail_from_name', $settings['mail_from_name'] ?? ($settings['site_name'] ?? '')) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">From Email</label>
                                <input type="email" name="mail_from_address" class="form-control"
                                       value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}"
                                       placeholder="noreply@yourdomain.com">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-bell" style="color:var(--lp-primary)"></i>
                        Notification Triggers
                    </div>
                    <div class="card-body">
                        @php $notifKeys = ['notify_on_signup'=>'New tenant signup','notify_on_payment'=>'Payment received','notify_on_expiry'=>'Expiry warning (7 & 3 days)','notify_on_expired'=>'Subscription expired']; @endphp
                        @foreach($notifKeys as $nKey => $nLabel)
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="{{ $nKey }}" value="1"
                                   id="{{ $nKey }}" {{ !empty($settings[$nKey]) ? 'checked' : '' }}>
                            <label class="form-check-label" for="{{ $nKey }}" style="font-size:.875rem;">{{ $nLabel }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-vial" style="color:var(--lp-primary)"></i>
                        Send Test Email
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2" style="font-size:.82rem;">Send a test email to verify your SMTP configuration.</p>
                        <div class="input-group">
                            <input type="email" id="testEmailAddr" class="form-control form-control-sm" placeholder="you@example.com">
                            <button type="button" class="btn btn-sm" onclick="sendTestEmail()"
                                    style="background:var(--lp-primary);color:#fff;border:none;">Send</button>
                        </div>
                        <div id="testEmailResult" class="mt-2" style="font-size:.8rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ SAVE BUTTON (hidden on Profile tab) ══ --}}
    <div class="d-flex justify-content-end mt-4 pt-2" id="settings-save-bar" style="border-top:1px solid #e2e8f0;">
        <button type="submit" class="btn btn-lg px-5"
                style="background:var(--lp-primary);color:#fff;border:none;border-radius:10px;font-weight:700;">
            <i class="fas fa-save me-2"></i>Save Settings
        </button>
    </div>

</form>

{{-- ══ PROFILE TAB (separate form — cannot be nested inside main form) ══ --}}
<div class="tab-pane" id="tab-profile">
    <form method="POST" action="{{ route('superadmin.profile.update') }}">
        @csrf @method('PUT')

        <div class="row g-4 justify-content-center">
            <div class="col-lg-6">

                {{-- Account info --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-id-card" style="color:var(--lp-primary)"></i>
                        Account Information
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', Auth::guard('superadmin')->user()->name) }}"
                                   placeholder="Your full name" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', Auth::guard('superadmin')->user()->email) }}"
                                   placeholder="admin@example.com" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                {{-- Change password --}}
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center gap-2">
                        <i class="fas fa-lock" style="color:var(--lp-primary)"></i>
                        Change Password
                        <span class="ms-auto text-muted" style="font-size:.75rem;">Leave blank to keep current password</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password"
                                       class="form-control @error('current_password') is-invalid @enderror"
                                       placeholder="Enter your current password" autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password') <div class="text-danger mt-1" style="font-size:.82rem;">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="new_password"
                                       class="form-control @error('new_password') is-invalid @enderror"
                                       placeholder="Min 8 characters, letters &amp; numbers" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                                       class="form-control"
                                       placeholder="Repeat new password" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePw('new_password_confirmation')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mt-3 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0;font-size:.8rem;color:#64748b;">
                            <i class="fas fa-info-circle me-1" style="color:var(--lp-primary)"></i>
                            Password must be at least 8 characters and include both letters and numbers.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-lg px-5"
                            style="background:var(--lp-primary);color:#fff;border:none;border-radius:10px;font-weight:700;">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

{{-- Standalone remove-logo form (outside main form to avoid nested forms) --}}
<form id="remove-logo-form" method="POST" action="{{ route('superadmin.settings.logo.remove') }}" style="display:none">
    @csrf @method('DELETE')
</form>

@push('scripts')
<script>
/* ── Tab switching ─────────────────────────────────────── */
function showTab(tabId, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.snav-btn').forEach(b => b.classList.remove('active'));
    const pane = document.getElementById('tab-' + tabId);
    if (pane) pane.classList.add('active');
    if (btn)  btn.classList.add('active');
    // Hide main save bar on profile tab (it has its own submit button)
    const saveBar = document.getElementById('settings-save-bar');
    if (saveBar) saveBar.style.display = (tabId === 'profile') ? 'none' : '';
    localStorage.setItem('settings_tab', tabId);
}
// Restore last tab — also support ?tab=profile redirect after profile save
const _urlTab  = new URLSearchParams(window.location.search).get('tab');
const _lastTab = _urlTab || localStorage.getItem('settings_tab');
const _lastBtn = _lastTab ? document.querySelector(`.snav-btn[onclick*="'${_lastTab}'"]`) : null;
if (_lastTab && document.getElementById('tab-' + _lastTab)) {
    showTab(_lastTab, _lastBtn);
} else {
    const firstBtn = document.querySelector('.snav-btn');
    if (firstBtn) showTab('theme', firstBtn);
}

/* ── Color pickers ─────────────────────────────────────── */
function syncColor(key) {
    const picker = document.getElementById('picker_' + key);
    const hex    = document.getElementById('hex_'    + key);
    const swatch = document.getElementById('swatch_' + key);
    if (!picker || !hex || !swatch) return;
    const val = picker.value.toUpperCase();
    hex.value = val; swatch.style.background = val;
    document.documentElement.style.setProperty('--lp-' + key.replace('theme_','').replace('_','-'), val);
}
function syncHex(key) {
    const hex = document.getElementById('hex_' + key);
    const picker = document.getElementById('picker_' + key);
    const swatch = document.getElementById('swatch_' + key);
    if (!hex || !picker || !swatch) return;
    const raw = hex.value.trim();
    const val = raw.startsWith('#') ? raw : '#' + raw;
    if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
        picker.value = val; swatch.style.background = val.toUpperCase(); hex.value = val.toUpperCase();
        document.documentElement.style.setProperty('--lp-' + key.replace('theme_','').replace('_','-'), val);
    }
}

/* Quick presets: fill all six colour fields (same order as the chips' args).
   Nothing is saved until "Save Settings". */
function applyPreset(primary, primaryLight, accent, lime, dark, cyan) {
    const values = {
        theme_primary: primary, theme_primary_light: primaryLight, theme_accent: accent,
        theme_lime: lime, theme_dark: dark, theme_cyan: cyan,
    };
    Object.entries(values).forEach(([key, val]) => {
        const hex = document.getElementById('hex_' + key);
        if (!hex) return;
        hex.value = val;
        syncHex(key);
    });
}

/* ── Font picker ──────────────────────────────────────── */
const loadedFonts = new Set();
function loadGoogleFont(fontName, slug) {
    if (loadedFonts.has(fontName)) return;
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${slug}:wght@400;600;700&display=swap`;
    document.head.appendChild(link);
    loadedFonts.add(fontName);
}
function selectFont(fontName, slug) {
    document.getElementById('selected_font_input').value = fontName;
    document.querySelectorAll('.font-card').forEach(c => {
        c.classList.remove('selected');
        const check = c.querySelector('.font-card-check');
        if (check) check.remove();
    });
    const active = document.querySelector(`.font-card[data-font="${fontName}"]`);
    if (active) {
        active.classList.add('selected');
        active.insertAdjacentHTML('beforeend', '<div class="font-card-check"><i class="fas fa-check-circle"></i></div>');
    }
    loadGoogleFont(fontName, slug);
    document.getElementById('preview-font-name').textContent = fontName;
    ['preview-headline','preview-body-text','preview-btn'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.fontFamily = `'${fontName}', sans-serif`;
    });
}

/* ── Logo preview ─────────────────────────────────────── */
function previewLogo(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('logo-preview-img');
        const ph  = document.getElementById('logo-placeholder-text');
        if (img) { img.src = e.target.result; img.style.display = 'block'; }
        if (ph)  ph.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}

/* ── Test email ───────────────────────────────────────── */
function sendTestEmail() {
    const addr = document.getElementById('testEmailAddr').value.trim();
    const res  = document.getElementById('testEmailResult');
    if (!addr) { res.innerHTML = '<span class="text-danger">Please enter an email address.</span>'; return; }
    res.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Sending…</span>';
    fetch('{{ route("superadmin.settings.test-email") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ email: addr })
    })
    .then(r => r.json())
    .then(d => {
        res.innerHTML = d.success
            ? '<span class="text-success"><i class="fas fa-check me-1"></i>Test email sent!</span>'
            : `<span class="text-danger"><i class="fas fa-times me-1"></i>${d.message ?? 'Failed.'}</span>`;
    })
    .catch(() => { res.innerHTML = '<span class="text-danger">Request failed.</span>'; });
}

/* ── Password visibility toggle ──────────────────────── */
function togglePw(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    field.type = (field.type === 'password') ? 'text' : 'password';
    const btn = field.nextElementSibling;
    if (btn) {
        const icon = btn.querySelector('i');
        if (icon) icon.className = (field.type === 'password') ? 'fas fa-eye' : 'fas fa-eye-slash';
    }
}

/* ── Open Profile tab if validation errors came from profile form ── */
@if($errors->has('name') || $errors->has('email') || $errors->has('current_password') || $errors->has('new_password') || session('profile_tab'))
    document.addEventListener('DOMContentLoaded', function () {
        const profileBtn = document.querySelector(".snav-btn[onclick*=\"'profile'\"]");
        if (profileBtn) showTab('profile', profileBtn);
    });
@endif

/* Preload current fonts */
document.querySelectorAll('.font-card').forEach(c => loadGoogleFont(c.dataset.font, c.dataset.slug));
</script>
@endpush
