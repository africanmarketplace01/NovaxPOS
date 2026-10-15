<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        try { $th = \App\Models\landlord\SaasSettings::allCached(); } catch (\Throwable $e) { $th = []; }
        $tp    = $th['theme_primary'] ?? '#0066CC';
        $tpl   = $th['theme_primary_light'] ?? '#00A3FF';
        $ta    = $th['theme_accent']  ?? '#66FF33';
        $td    = $th['theme_dark']    ?? '#003366';
        $siteN = $th['site_name']     ?? 'NovaxPOS';
        $siteLogo = $th['site_logo'] ?? null;
        $siteLogoUrl = ($siteLogo && file_exists(public_path('landlord/images/logo/' . $siteLogo)))
                       ? asset('landlord/images/logo/' . $siteLogo) : null;
    @endphp
    <title>Setup Wizard — {{ $siteN }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">
    <style>
        :root { --lp-primary:{{ $tp }}; --lp-primary-light:{{ $tpl }}; --lp-accent:{{ $ta }}; --lp-dark:{{ $td }}; }
        *, *::before, *::after { box-sizing:border-box; }
        body { background:linear-gradient(135deg,{{ $td }},{{ $tp }}); min-height:100vh;
               display:flex; align-items:center; justify-content:center; padding:30px 15px;
               font-family:'Segoe UI',sans-serif; }

        .wizard-wrap { background:#fff; border-radius:24px; box-shadow:0 24px 80px rgba(0,0,0,.2);
                       width:100%; max-width:680px; overflow:hidden; }

        /* Progress bar */
        .wizard-progress { display:flex; border-bottom:2px solid #f1f5f9; }
        .wizard-step-pill { flex:1; text-align:center; padding:.75rem .5rem; font-size:.75rem;
                            font-weight:600; color:#94a3b8; position:relative; transition:all .25s; }
        .wizard-step-pill.done   { color:var(--lp-primary); }
        .wizard-step-pill.active { color:var(--lp-primary); }
        .wizard-step-pill .step-num {
            width:28px; height:28px; border-radius:50%; border:2px solid #e2e8f0;
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 4px; font-size:.8rem; font-weight:700; transition:all .25s;
        }
        .wizard-step-pill.active .step-num { border-color:var(--lp-primary); color:var(--lp-primary); background:rgba(0,102,204,.06); }
        .wizard-step-pill.done   .step-num { border-color:var(--lp-primary); background:var(--lp-primary); color:#fff; }
        .wizard-step-pill::after {
            content:''; position:absolute; bottom:-2px; left:0; width:100%; height:2px;
            background:transparent; transition:background .25s;
        }
        .wizard-step-pill.active::after { background:var(--lp-primary); }

        /* Body */
        .wizard-body { padding:2.5rem; }
        .step-panel { display:none; }
        .step-panel.active { display:block; }

        .form-label { font-size:.84rem; font-weight:600; color:#374151; }
        .form-control, .form-select { border-radius:8px; font-size:.875rem; border-color:#e2e8f0; }
        .form-control:focus, .form-select:focus {
            border-color:var(--lp-primary); box-shadow:0 0 0 3px rgba(0,102,204,.08);
        }

        /* Buttons */
        .btn-next { background:var(--lp-primary); color:#fff; border:none; border-radius:10px;
                    padding:.65rem 2rem; font-weight:700; font-size:.9rem; transition:background .2s; }
        .btn-next:hover { background:var(--lp-primary-light); }
        .btn-prev { background:transparent; color:#64748b; border:1.5px solid #e2e8f0; border-radius:10px;
                    padding:.65rem 1.5rem; font-weight:600; font-size:.9rem; transition:all .2s; }
        .btn-prev:hover { border-color:#94a3b8; color:#374151; }

        /* Business type chips */
        .biz-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:.5rem; }
        .biz-chip { padding:.45rem 1rem; border-radius:999px; border:1.5px solid #e2e8f0;
                    font-size:.82rem; font-weight:600; cursor:pointer; transition:all .15s; background:#fff; color:#64748b; }
        .biz-chip:hover, .biz-chip.selected { border-color:var(--lp-primary); color:var(--lp-primary); background:rgba(0,102,204,.05); }

        /* Success step */
        .success-icon { width:80px; height:80px; border-radius:50%; background:rgba(16,185,129,.1);
                        color:#10b981; display:flex; align-items:center; justify-content:center;
                        font-size:2rem; margin:0 auto 1.25rem; }
        .confetti-row { display:flex; justify-content:center; gap:4px; margin-bottom:1rem; font-size:1.5rem; }
    </style>
    {{-- LIBServices font and type scale (shared with the back office). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/lib-type.css">
</head>
<body>
<div class="wizard-wrap">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between px-4 pt-4 pb-3">
        @if($siteLogoUrl)
            <img src="{{ $siteLogoUrl }}" alt="{{ $siteN }}" style="max-height:36px;max-width:140px;object-fit:contain;">
        @else
            <div style="font-size:1.15rem;font-weight:800;color:var(--lp-dark);">{{ $siteN }}<span style="color:var(--lp-primary);">.</span></div>
        @endif
        <a href="{{ route('onboarding.skip') }}" class="text-muted text-decoration-none" style="font-size:.8rem;">
            Skip setup <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>

    {{-- Progress pills --}}
    <div class="wizard-progress">
        <div class="wizard-step-pill active" id="pill-1">
            <div class="step-num">1</div>Your Store
        </div>
        <div class="wizard-step-pill" id="pill-2">
            <div class="step-num">2</div>Currency
        </div>
        <div class="wizard-step-pill" id="pill-3">
            <div class="step-num">3</div>Admin
        </div>
        <div class="wizard-step-pill" id="pill-4">
            <div class="step-num">4</div>Done!
        </div>
    </div>

    {{-- Body --}}
    <div class="wizard-body">

        {{-- Step 1: Store Details --}}
        <div class="step-panel active" id="step-1">
            <h5 class="fw-bold mb-1">Welcome! Let's set up your store 🎉</h5>
            <p class="text-muted mb-4" style="font-size:.875rem;">This takes less than 2 minutes.</p>
            <div class="mb-3">
                <label class="form-label">Store / Business Name <span class="text-danger">*</span></label>
                <input type="text" id="s1_site_title" class="form-control"
                       value="{{ old('site_title') }}" placeholder="e.g. Acme Superstore" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Business Type</label>
                <div class="biz-chips">
                    @foreach(['Retail Shop','Restaurant','Pharmacy','Electronics','Fashion','Supermarket','Other'] as $biz)
                    <div class="biz-chip" onclick="selectBiz(this, '{{ $biz }}')">{{ $biz }}</div>
                    @endforeach
                </div>
                <input type="hidden" id="s1_business_type" value="">
            </div>
            <div class="mb-4">
                <label class="form-label">Timezone</label>
                <select id="s1_timezone" class="form-select">
                    @foreach($timezones as $tz)
                    <option value="{{ $tz }}" {{ $tz === 'UTC' ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex justify-content-end">
                <button class="btn-next" onclick="submitStep(1)">
                    Continue <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>

        {{-- Step 2: Currency --}}
        <div class="step-panel" id="step-2">
            <h5 class="fw-bold mb-1">Currency & Region</h5>
            <p class="text-muted mb-4" style="font-size:.875rem;">Choose your primary currency for sales and reporting.</p>
            <div class="mb-3">
                <label class="form-label">Currency <span class="text-danger">*</span></label>
                <select id="s2_currency" class="form-select" onchange="updateSymbol(this)">
                    @foreach($currencies as $code => $label)
                    <option value="{{ $code }}" data-symbol="{{ ['USD'=>'$','EUR'=>'€','GBP'=>'£','CAD'=>'CA$','AUD'=>'A$','NGN'=>'₦','GHS'=>'₵','KES'=>'KSh','ZAR'=>'R'][$code] ?? $code }}">
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">Currency Symbol</label>
                <input type="text" id="s2_currency_symbol" class="form-control" value="$" maxlength="5"
                       placeholder="$" style="max-width:100px;">
                <div class="form-text">This symbol appears on receipts and invoices.</div>
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn-prev" onclick="goStep(1)"><i class="fas fa-arrow-left me-2"></i>Back</button>
                <button class="btn-next" onclick="submitStep(2)">Continue <i class="fas fa-arrow-right ms-2"></i></button>
            </div>
        </div>

        {{-- Step 3: Admin --}}
        <div class="step-panel" id="step-3">
            <h5 class="fw-bold mb-1">Your Admin Account</h5>
            <p class="text-muted mb-4" style="font-size:.875rem;">Confirm or update your display name.</p>
            <div class="mb-4">
                <label class="form-label">Your Name</label>
                <input type="text" id="s3_admin_name" class="form-control"
                       value="{{ auth()->user()->name ?? '' }}" placeholder="Your full name">
            </div>
            <div class="p-3 rounded mb-4" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                <div class="d-flex align-items-start gap-2">
                    <i class="fas fa-check-circle mt-1" style="color:#10b981;"></i>
                    <div style="font-size:.85rem;color:#064e3b;">
                        <strong>Admin role</strong> is active. You have full access to all features.
                        You can invite additional staff from <strong>Users → Create User</strong> in the sidebar.
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between">
                <button class="btn-prev" onclick="goStep(2)"><i class="fas fa-arrow-left me-2"></i>Back</button>
                <button class="btn-next" onclick="submitStep(3)">Continue <i class="fas fa-arrow-right ms-2"></i></button>
            </div>
        </div>

        {{-- Step 4: Done --}}
        <div class="step-panel" id="step-4">
            <div class="text-center py-3">
                <div class="confetti-row">🎉 🚀 ✨</div>
                <div class="success-icon"><i class="fas fa-check"></i></div>
                <h5 class="fw-bold mb-2">You're all set!</h5>
                <p class="text-muted mb-4" style="font-size:.9rem;">
                    Your store is configured and ready to go.<br>
                    Start adding products, setting up your POS, and making sales.
                </p>
                <div class="d-flex flex-column gap-2 align-items-center" style="max-width:300px;margin:0 auto;">
                    <button class="btn-next w-100" onclick="completeSetup()">
                        <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                    </button>
                </div>
                <div class="mt-4 d-flex justify-content-center gap-4 flex-wrap" style="font-size:.82rem;color:#64748b;">
                    <span><i class="fas fa-box me-1" style="color:var(--lp-primary)"></i>Add Products</span>
                    <span><i class="fas fa-cash-register me-1" style="color:var(--lp-primary)"></i>Open POS</span>
                    <span><i class="fas fa-users me-1" style="color:var(--lp-primary)"></i>Invite Staff</span>
                </div>
            </div>
        </div>

        {{-- Error alert --}}
        <div id="wizard-error" class="alert alert-danger py-2 mt-3" style="display:none;font-size:.85rem;"></div>

    </div>{{-- /.wizard-body --}}
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF = '{{ csrf_token() }}';
let currentStep = 1;

function goStep(n) {
    document.querySelectorAll('.step-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('step-' + n).classList.add('active');

    document.querySelectorAll('.wizard-step-pill').forEach((pill, i) => {
        pill.classList.remove('active','done');
        if (i + 1 < n)  pill.classList.add('done');
        if (i + 1 === n) pill.classList.add('active');
        // Replace number with check for done steps
        const num = pill.querySelector('.step-num');
        if (i + 1 < n) {
            num.innerHTML = '<i class="fas fa-check" style="font-size:.7rem;"></i>';
        } else {
            num.textContent = i + 1;
        }
    });

    currentStep = n;
    document.getElementById('wizard-error').style.display = 'none';
}

function selectBiz(el, val) {
    document.querySelectorAll('.biz-chip').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('s1_business_type').value = val;
}

function updateSymbol(sel) {
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('s2_currency_symbol').value = opt.dataset.symbol || '';
}

function getStepPayload(step) {
    const payloads = {
        1: { step: 1, site_title: document.getElementById('s1_site_title').value,
                       business_type: document.getElementById('s1_business_type').value,
                       timezone: document.getElementById('s1_timezone').value },
        2: { step: 2, currency: document.getElementById('s2_currency').value,
                       currency_symbol: document.getElementById('s2_currency_symbol').value },
        3: { step: 3, admin_name: document.getElementById('s3_admin_name').value },
        4: { step: 4 },
    };
    return payloads[step] || {};
}

function submitStep(step) {
    const errEl = document.getElementById('wizard-error');
    errEl.style.display = 'none';

    if (step === 1 && !document.getElementById('s1_site_title').value.trim()) {
        errEl.textContent = 'Please enter your store name.';
        errEl.style.display = 'block';
        return;
    }

    fetch('{{ route("onboarding.step") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(getStepPayload(step))
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            goStep(step + 1);
        } else {
            errEl.textContent = d.message ?? 'Something went wrong. Please try again.';
            errEl.style.display = 'block';
        }
    })
    .catch(() => {
        errEl.textContent = 'Network error. Please check your connection.';
        errEl.style.display = 'block';
    });
}

function completeSetup() {
    const btn = document.querySelector('#step-4 .btn-next');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Redirecting…';

    fetch('{{ route("onboarding.step") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      