@extends('landlord.marketplace.layout')
@section('title', 'My Orders')

@section('content')
<div class="container py-4">
  <div class="acct-shell">
    @include('landlord.customer._nav', ['active' => 'orders'])
    <div class="acct-main">
      <h4 class="fw-bold mb-3">My Orders</h4>

      <div class="bg-white rounded-3" style="border:1px solid var(--mp-border)">
        @forelse($orders as $o)
          <div class="p-3 {{ !$loop->last?'border-bottom':'' }}">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <div class="fw-semibold">{{ $o->order_number }}</div>
                <div class="text-muted" style="font-size:var(--text-xs)">{{ $o->created_at?->format('M j, Y g:i A') }}</div>
              </div>
              <div class="text-end">
                <div class="fw-bold">{{ $o->currency }} {{ number_format($o->total,2) }}</div>
                <span class="badge bg-{{ $o->statusBadge() }} text-capitalize">{{ $o->status }}</span>
                <span class="badge bg-{{ $o->paymentBadge() }} text-capitalize">{{ $o->payment_status }}</span>
              </div>
            </div>
            @if(is_array($o->items) && count($o->items))
              <div class="mt-2 text-muted" style="font-size:var(--text-xs)">
                {{ collect($o->items)->take(3)->pluck('name')->implode(', ') }}{{ count($o->items) > 3 ? '…' : '' }}
              </div>
            @endif
            <div class="mt-2">
              <a href="{{ route('customer.orders.show', $o->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-truck-fast me-1"></i> Track &amp; view order
              </a>
            </div>
          </div>
        @empty
          <div class="p-5 text-center text-muted">
            <i class="fas fa-box-open mb-2 d-block" style="font-size:2rem;opacity:.4"></i>
            You haven't placed any orders yet.
            <div class="mt-2"><a href="{{ route('marketplace.index') }}" class="btn btn-sm btn-primary">Browse marketplace</a></div>
          </div>
        @endforelse
      </div>

      <div class="mt-3">{{ $orders->links() }}</div>
    </div>
  </div>
</div>
@endsection
