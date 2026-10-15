<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php try { $th = \App\Models\landlord\SaasSettings::allCached(); } catch (\Throwable $e) { $th = []; }
        $siteN = $th['site_name'] ?? 'NovaxPOS';
        $tp    = $th['theme_primary'] ?? '#0066CC';
    @endphp
    <title>Registration Submitted — {{ $siteN }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('fontawesome/css/all.min.css') }}" rel="stylesheet">
    {{-- LIBServices font and type scale (shared with the back office). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/lib-type.css">
</head>
<body style="background:#f8fafc;font-family:'Segoe UI',sans-serif">
<div class="container py-5 text-center" style="max-width:560px">
    <div style="width:80px;height:80px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;border:2px solid #22c55e">
        <i class="fas fa-check" style="font-size:2rem;color:#22c55e"></i>
    </div>
    <h2 class="fw-bold mb-2">Application Submitted!</h2>
    @if(session('agency_name'))
    <p class="text-muted">Thank you, <strong>{{ session('agency_name') }}</strong>. Your delivery agency registration has been received and is pending review.</p>
    @else
    <p class="text-muted">Your delivery agency registration has been received and is pending review.</p>
    @endif
    <div class="bg-white rounded-3 p-4 mt-4 text-start" style="border:1px solid #e2e8f0">
        <h6 class="fw-bold mb-3">What happens next?</h6>
        <div class="d-flex gap-3 mb-2 align-items-start">
            <div style="width:28px;height:28px;background:{{ $tp }}20;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0"><span style="color:{{ $tp }};font-weight:700;font-size:.8rem">1</span></div>
            <div class="small"><strong>Review</strong> — Our team will review your application within 24–48 hours.</div>
        </div>
        <div class="d-flex gap-3 mb-2 align-items-start">
            <div style="width:28px;height:28px;background:{{ $tp }}20;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0"><span style="color:{{ $tp }};font-weight:700;font-size:.8rem">2</span></div>
            <div class="small"><strong>Notification</strong> — You'll receive an email with the decision.</div>
        </div>
        <div class="d-flex gap-3 align-items-start">
            <div style="width:28px;height:28px;background:{{ $tp }}20;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0"><span style="color:{{ $tp }};font-weight:700;font-size:.8rem">3</span></div>
            <div class="small"><strong>Go live</strong> — Once approved, you'll appear as a delivery option at checkout.</div>
        </div>
    </div>
    <a href="{{ route('marketplace.index') }}" class="btn mt-4" style="background:{{ $tp }};color:#fff;font-weight:600;border-radius:8px;padding:.6rem 2rem">
        Browse Marketplace
    </a>
</div>
</body>
</html>
