@extends('landlord.marketplace.layout')
@section('title', 'My Account')

@section('content')
<div class="container py-4">
  <div class="acct-shell">
    @include('landlord.customer._nav', ['active' => 'dashboard'])

    <div class="acct-main">
      <h4 class="fw-bold mb-1">Hi {{ Str::before($c->name, ' ') }} 👋</h4>
      <p class="text-muted" style="font-size:var(--text-sm)">Here's what's happening with your account.</p>

      {{-- Stat cards --}}
      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <div class="p-3 bg-white rounded-3 h-100" style="border:1px solid var(--mp-border)">
            <div class="text-muted" style="font-size:var(--text-xs);text-transform:uppercase;letter-spacing:.05em">Orders</div>
            <div class="fw-bold" style="font-size:1.7rem;color: var(--mp-link)">{{ $stats['orders'] }}</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="p-3 bg-white rounded-3 h-100" style="border:1px solid var(--mp-border)">
            <div class="text-muted" style="font-size:var(--text-xs);text-transform:uppercase;letter-spacing:.05em">Open Requests</div>
            <div class="fw-bold" style="font-size:1.7rem;color:#f59e0b">{{ $stats['open_requests'] }}</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="p-3 bg-white rounded-3 h-100" style="border:1px solid var(--mp-border)">
            <div class="text-muted" style="font-size:var(--text-xs);text-transform:uppercase;letter-spacing:.05em">Vendor Responses</div>
            <div class="fw-bold" style="font-size:1.7rem;color:#22c55e">{{ $stats['responses'] }}</div>
          </div>
        </div>
      </div>

      <div class="p-3 mb-4 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
           style="background:linear-gradient(135deg,var(--mp-dark),var(--mp-primary));color:#fff">
        <div>
          <div class="fw-bold">Can't find a product or a spare part?</div>
          <div style="font-size:var(--text-sm);opacity:.85">Post what you're looking for — optionally with a short video — and every vendor can respond.</div>
        </div>
        <a href="{{ route('customer.requests.create') }}" class="btn btn-light btn-sm fw-semibold"><i class="fas fa-plus me-1"></i> New Request</a>
      </div>

      {{-- Recent orders --}}
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold mb-0">Recent Orders</h6>
        <a href="{{ route('customer.orders') }}" style="font-size:var(--text-sm)">View all →</a>
      </div>
      <div class="bg-white rounded-3 mb-4" style="border:1px solid var(--mp-border)">
        @forelse($orders as $o)
          <a href="{{ route('customer.orders.show', $o->id) }}" class="d-flex align-items-center justify-content-between p-3 text-decoration-none text-dark {{ !$loop->last?'border-bottom':'' }}">
            <div>
              <div class="fw-semibold" style="font-size:var(--text-sm)">{{ $o->order_number }}</div>
              <div class="text-muted" style="font-size:var(--text-xs)">{{ $o->created_at?->format('M j, Y') }} · {{ is_array($o->items)?count($o->items):0 }} item(s)</div>
            </div>
            <div class="text-end">
              <div class="fw-bold" style="font-size:var(--text-sm)">{{ $o->currency }} {{ number_format($o->total,2) }}</div>
              <span class="badge bg-{{ $o->statusBadge() }} text-capitalize">{{ $o->status }}</span>
            </div>
          </a>
        @empty
          <div class="p-4 text-center text-muted" style="font-size:var(--text-sm)">No orders yet. <a href="{{ route('marketplace.index') }}">Start shopping →</a></div>
        @endforelse
      </div>

      {{-- Recent requests --}}
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold mb-0">Recent Requests</h6>
        <a href="{{ route('customer.requests.index') }}" style="font-size:var(--text-sm)">View all →</a>
      </div>
      <div class="bg-white rounded-3" style="border:1px solid var(--mp-border)">
        @forelse($requests as $r)
          <a href="{{ route('customer.requests.show', $r->id) }}" class="d-flex align-items-center justify-content-between p-3 text-decoration-none text-dark {{ !$loop->last?'border-bottom':'' }}">
            <div>
              <div class="fw-semibold" style="font-size:var(--text-sm)">{{ Str::limit($r->title, 46) }}</div>
              <div class="text-muted" style="font-size:var(--text-xs)">{{ $r->created_at?->diffForHumans() }}</div>
            </div>
            <span class="badge {{ $r->status==='open'?'bg-success':'bg-secondary' }} text-capitalize">{{ $r->status }}</span>
          </a>
        @empty
          <div class="p-4 text-center text-muted" style="font-size:var(--text-sm)">No requests yet. <a href="{{ route('customer.requests.create') }}">Post your first request →</a></div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
