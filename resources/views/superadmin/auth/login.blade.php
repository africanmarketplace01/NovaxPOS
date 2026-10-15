<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NovaxPOS — Superadmin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">
    <style>
        body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: #1e293b; border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 420px; box-shadow: 0 25px 50px rgba(0,0,0,.4); }
        .brand-logo { font-size: 1.5rem; font-weight: 700; color: #6366f1; letter-spacing: .5px; }
        .brand-logo span { color: #a5b4fc; }
        .form-control { background: #0f172a; border-color: #334155; color: #e2e8f0; }
        .form-control:focus { background: #0f172a; border-color: #6366f1; color: #e2e8f0; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }
        .form-label { color: #94a3b8; font-size: .85rem; }
        .btn-primary { background: #6366f1; border-color: #6366f1; }
        .btn-primary:hover { background: #4f46e5; border-color: #4f46e5; }
        .alert-danger { background: #450a0a; border-color: #dc2626; color: #fca5a5; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand-logo">Novax<span>POS</span></div>
            <p class="text-secondary mt-1 mb-0" style="font-size:.85rem;">Superadmin Portal</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-success py-2">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('superadmin.login.post') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" placeholder="admin@novaxpos.com" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label text-secondary" style="font-size:.85rem;" for="remember">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>
    </div>
</body>
</html>
