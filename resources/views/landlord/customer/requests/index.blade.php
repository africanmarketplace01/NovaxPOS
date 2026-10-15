@extends('landlord.marketplace.layout')
@section('title', 'Sourcing Requests')

@section('content')
<div class="container py-4">
  <div class="acct-shell">
    @include('landlord.customer._nav', ['active' => 'requests'])
    <div class="acct-main">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="fw-bold mb-0">Sourcing Requests</h4>
        <a href="{{ route('customer.requests.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> New Request</a>
      </div>

      @if(session('success'))<div class="mp-flash mp-flash-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif

      <div class="d-flex flex-column gap-2">
        @forelse($requests as $r)
          <a href="{{ route('customer.requests.show', $r->id) }}" class="bg-white rounded-3 p-3 text-decoration-none text-dark d-flex gap-3 align-items-center" style="border:1px solid var(--mp-border)">
            @if($r->imageUrl())
              <img src="{{ $r->imageUrl() }}" style="width:56px;height:56px;object-fit:cover;border-radius:10px" alt="">
            @elseif($r->video_path)
              <div style="width:56px;height:56px;border-radius:10px;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center"><i class="fas fa-play"></i></div>
            @else
              <div style="width:56px;height:56px;border-radius:10px;background:#f1f5f9;color:#94a3b8;display:flex;align-items:center;justify-content:center"><i class="fas fa-bullhorn"></i></div>
            @endif
            <div class="flex-grow-1" style="min-width:0">
              <div class="fw-semibold" style="font-size:var(--text-sm)">{{ Str::limit($r->title, 60) }}</div>
              <div class="text-muted" style="font-size:var(--text-xs)">{{ $r->created_at?->diffForHumans() }} @if($r->category) · {{ $r->category }} @endif</div>
            </div>
            <div class="text-end">
              <span class="badge bg-primary">{{ $r->responses_count }} response{{ $r->responses_count==1?'':'s' }}</span>
              <div><span class="badge {{ $r->status==='open'?'bg-success':'bg-secondary' }} text-capitalize mt-1">{{ $r->status }}</span></div>
            </div>
          </a>
        @empty
          <div class="bg-white rounded-3 p-5 text-center text-muted" style="border:1px solid var(--mp-border)">
            <i class="fas fa-bullhorn mb-2 d-block" style="font-size:2rem;opacity:.4"></i>
            <div class="fw-semibold mb-1" style="color:#475569">No requests yet</div>
            <p style="font-size:var(--text-sm)">Looking for a specific product or spare part? Post a request and let vendors come to you.</p>
            <a href="{{ route('customer.requests.create') }}" class="btn btn-primary btn-sm">Post a request</a>
          </div>
        @endforelse
      </div>

      <div class="mt-3">{{ $requests->links() }}</div>
    </div>
  </div>
</div>
@endsection
