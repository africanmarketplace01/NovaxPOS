<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        try { $th = \App\Models\landlord\SaasSettings::allCached(); } catch (\Throwable $e) { $th = []; }
        $siteN = $th['site_name'] ?? 'NovaxPOS';
        $tp    = $th['theme_primary'] ?? '#0066CC';
        $td    = $th['theme_dark']   ?? '#003366';
        $ta    = $th['theme_accent'] ?? '#66FF33';
    @endphp
    <title>Delivery Agency Registration — {{ $siteN }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('fontawesome/css/all.min.css') }}" rel="stylesheet">
    <style>
        body { background:#f8fafc; font-family:'Segoe UI',sans-serif; }
        .reg-nav { background:{{ $td }}; padding:.75rem 0; }
        .reg-nav a { color:#fff; text-decoration:none; font-weight:700; font-size:1.1rem; }
        .card-form { border-radius:16px; border:1px solid #e2e8f0; padding:2rem; }
        .section-title { font-weight:700; font-size:.8rem; text-transform:uppercase; letter-spacing:.06em; color:#64748b; margin-bottom:1rem; padding-bottom:.5rem; border-bottom:1px solid #e2e8f0; }
        .btn-primary-custom { background:{{ $tp }}; border:none; color:#fff; font-weight:600; border-radius:8px; padding:.65rem 2rem; }
        .btn-primary-custom:hover { background:{{ $tp }}dd; }
    </style>
    {{-- LIBServices font and type scale (shared with the back office). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/lib-type.css">
</head>
<body>
<nav class="reg-nav">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="{{ route('landing') }}">{{ $siteN }}</a>
        <a href="{{ route('marketplace.index') }}" style="font-size:.85rem;color:rgba(255,255,255,.7)">Browse Marketplace →</a>
    </div>
</nav>

<div class="container py-5" style="max-width:760px">
    <div class="text-center mb-4">
        <div style="width:64px;height:64px;background:{{ $tp }}20;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
            <i class="fas fa-truck" style="font-size:1.6rem;color:{{ $tp }}"></i>
        </div>
        <h2 class="fw-bold">Register as a Delivery Agency</h2>
        <p class="text-muted">Join the {{ $siteN }} marketplace network and start delivering for local businesses.</p>
    </div>

    @if($errors->any())
    <div class="alert alert-danger mb-3">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('delivery.register.submit') }}" method="POST" enctype="multipart/form-data" class="bg-white card-form">
        @csrf

        {{-- Business info --}}
        <div class="section-title">Business Information</div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.875rem">Agency Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.875rem">Contact Person Name *</label>
                <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.875rem">Email Address *</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.875rem">Phone Number *</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:.875rem">Business Address</label>
                <input type="text" name="address" class="form-control" value="{{ old('address') }}" placeholder="Street, city, country">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:.875rem">Agency Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
                <div class="form-text">PNG, JPG, WEBP — max 2MB</div>
            </div>
        </div>

        {{-- Service details --}}
        <div class="section-title">Service Details</div>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:.875rem">Coverage Areas *</label>
                <input type="text" name="coverage_areas" class="form-control" value="{{ old('coverage_areas') }}"
                       placeholder="e.g. Monrovia, Paynesville, Margibi County" required>
                <div class="form-text">Comma-separated cities or regions you cover</div>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:.875rem">Description / About</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Tell customers about your service…">{{ old('description') }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.875rem">Base Delivery Rate *</label>
                <div class="input-group">
                    <input type="number" name="base_rate" class="form-control" step="0.01" min="0" value="{{ old('base_rate',0) }}" required>
                    <select name="currency" class="form-select" style="max-width:80px">
                        <option {{ old('currency')=='USD' ? 'selected' : '' }}>USD</option>
                        <option {{ old('currency')=='LRD' ? 'selected' : '' }}>LRD</option>
                        <option {{ old('currency')=='NGN' ? 'selected' : '' }}>NGN</option>
                        <option {{ old('currency')=='GHS' ? 'selected' : '' }}>GHS</option>
                    </select>
                </div>
                <div class="form-text">Base fee per delivery</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:.875rem">Per-km Rate (optional)</label>
                <input type="number" name="per_km_rate" class="form-control" step="0.01" min="0" value="{{ old('per_km_rate',0) }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <div class="flex-fill">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Est. Days (min) *</label>
                    <input type="number" name="estimated_days_min" class="form-control" min="1" max="60" value="{{ old('estimated_days_min',1) }}" required>
                </div>
                <div class="flex-fill">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Est. Days (max) *</label>
                    <input type="number" name="estimated_days_max" class="form-control" min="1" max="60" value="{{ old('estimated_days_max',3) }}" required>
                </div>
            </div>
        </div>

        {{-- Account --}}
        <div class="section-title">Account Password</div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.875rem">Password *</label>
                <input type="password" name="password" class="form-control" required minlength="8">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:.875rem">Confirm Password *</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
        </div>

        <div class="d-flex gap-3 align-items-center">
            <button type="submit" class="btn-primary-custom">
                <i class="fas fa-paper-plane me-2"></i>Submit Registration
            </button>
            <span class="text-muted small">Your application will be reviewed by our team within 24–48 hours.</span>
        </div>
    </form>
</div>
</body>
</html>
