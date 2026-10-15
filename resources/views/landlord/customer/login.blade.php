@extends('landlord.marketplace.layout')
@section('title', 'Sign in')

@section('extra-css')
<style>
.auth-wrap { max-width: 430px; margin: 2.5rem auto; }
.auth-card { background:#fff; border:1px solid var(--mp-border); border-radius:16px; padding:2rem; box-shadow:var(--mp-shadow); }
.auth-title { font-size:1.4rem; font-weight:800; color:#0f172a; margin-bottom:.35rem; }
.auth-sub { font-size:var(--text-sm); color:#64748b; margin-bottom:1.5rem; }
.auth-card label { font-size:var(--text-xs); font-weight:600; color:#334155; margin-bottom:.3rem; display:block; }
.auth-card .form-control { border:1.5px solid var(--mp-border); border-radius:9px; padding:.6rem .85rem; font-size:var(--text-sm); width:100%; }
.auth-card .form-control:focus { border-color:var(--mp-primary); outline:none; box-shadow:0 0 0 3px rgba(0,102,204,.08); }
.auth-btn { width:100%; background:var(--mp-primary); color: var(--mp-on-primary); border:none; border-radius:9px; padding:.7rem; font-weight:700; font-size:var(--text-sm); cursor:pointer; transition:background .2s; }
.auth-btn:hover { background:var(--mp-light); }
.auth-alt { text-align:center; font-size:var(--text-sm); color:#64748b; margin-top:1.2rem; }
.auth-alt a { color: var(--mp-link); font-weight:600; }
</style>
@endsection

@section('content')
<div class="container">
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-title">Welcome back</div>
      <div class="auth-sub">Sign in to track orders, post sourcing requests and chat with vendors.</div>

      @if($errors->any())
        <div class="mp-flash mp-flash-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
      @endif

      <form action="{{ route('customer.login.post') }}" method="POST">
        @csrf
        <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="mb-3">
          <label>Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3 d-flex align-items-center gap-2">
          <input type="checkbox" name="remember" id="remember" value="1">
          <label for="remember" style="margin:0;font-weight:500">Keep me signed in</label>
        </div>
        <button type="submit" class="auth-btn"><i class="fas fa-sign-in-alt me-1"></i> Sign in</button>
      </form>

      <div class="auth-alt">New here? <a href="{{ route('customer.register') }}">Create an account</a></div>
    </div>
  </div>
</div>
@endsection
