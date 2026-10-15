{{-- Customer account sub-navigation. $active = dashboard|orders|requests|profile --}}
@php $active = $active ?? ''; @endphp
<style>
.acct-shell { display:grid; grid-template-columns:230px 1fr; gap:1.5rem; }
@media (max-width:860px){ .acct-shell{ grid-template-columns:1fr; } }
.acct-side { background:#fff; border:1px solid var(--mp-border); border-radius:14px; padding:1rem; align-self:start; }
.acct-user { display:flex; align-items:center; gap:.7rem; padding:.4rem .3rem 1rem; border-bottom:1px solid var(--mp-border); margin-bottom:.6rem; }
.acct-avatar { width:44px; height:44px; border-radius:50%; background:var(--mp-primary); color: var(--mp-on-primary); display:flex; align-items:center; justify-content:center; font-weight:800; }
.acct-user .nm { font-weight:700; font-size:var(--text-sm); color:#0f172a; }
.acct-user .em { font-size:var(--text-xs); color:#94a3b8; }
.acct-link { display:flex; align-items:center; gap:.6rem; padding:.6rem .7rem; border-radius:9px; font-size:var(--text-sm); color:#475569; text-decoration:none; margin-bottom:.15rem; }
.acct-link i { width:18px; text-align:center; color:#94a3b8; }
.acct-link:hover { background:#f1f5f9; color: var(--mp-link); }
.acct-link.active { background:var(--mp-primary); color: var(--mp-on-primary); }
.acct-link.active i { color:#fff; }
.acct-main { min-width:0; }
</style>
<div class="acct-side">
  <div class="acct-user">
    <div class="acct-avatar">{{ $c->initials }}</div>
    <div style="min-width:0">
      <div class="nm">{{ Str::limit($c->name, 16) }}</div>
      <div class="em">{{ Str::limit($c->email, 20) }}</div>
    </div>
  </div>
  <a href="{{ route('customer.dashboard') }}" class="acct-link {{ $active==='dashboard'?'active':'' }}"><i class="fas fa-gauge"></i> Dashboard</a>
  <a href="{{ route('customer.orders') }}" class="acct-link {{ $active==='orders'?'active':'' }}"><i class="fas fa-box"></i> My Orders</a>
  <a href="{{ route('customer.requests.index') }}" class="acct-link {{ $active==='requests'?'active':'' }}"><i class="fas fa-bullhorn"></i> Sourcing Requests</a>
  <a href="{{ route('customer.profile') }}" class="acct-link {{ $active==='profile'?'active':'' }}"><i class="fas fa-user-gear"></i> Profile</a>
  <form action="{{ route('customer.logout') }}" method="POST" class="m-0 mt-1">@csrf
    <button class="acct-link w-100 border-0 bg-transparent text-start"><i class="fas fa-right-from-bracket"></i> Sign out</button>
  </form>
</div>
