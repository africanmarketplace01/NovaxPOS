@extends('backend.layout.main')
@section('content')

<style>
.sr-wrap { padding: 8px 4px; }
.sr-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:16px; }
.sr-card { background:#fff; border:1px solid #e6e9f0; border-radius:12px; padding:16px; margin-bottom:14px; display:flex; gap:14px; }
.sr-thumb { width:74px; height:74px; border-radius:10px; flex-shrink:0; object-fit:cover; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:1.4rem; }
.sr-title { font-weight:700; font-size:1rem; color:#1e293b; margin:0 0 3px; }
.sr-meta { font-size:.76rem; color:#94a3b8; }
.sr-desc { font-size:.85rem; color:#475569; margin:.4rem 0 .2rem; }
.sr-badge { font-size:.68rem; font-weight:700; padding:.2em .6em; border-radius:20px; }
.sr-badge.done { background:#dcfce7; color:#166534; }
.sr-badge.new { background:#fef3c7; color:#92400e; }
.sr-btn { background:var(--lp-primary,#0066CC); color:#fff; border:none; border-radius:8px; padding:.45rem 1rem; font-size:.82rem; font-weight:600; text-decoration:none; white-space:nowrap; }
.sr-btn:hover { color:#fff; opacity:.92; }
</style>

<div class="sr-wrap">
  <div class="sr-head">
    <div>
      <h4 style="font-weight:700;margin:0">Marketplace Requests</h4>
      <div style="font-size:.83rem;color:#94a3b8">Customers looking for products — respond with an offer and chat directly.</div>
    </div>
  </div>

  @if(session('message'))
    <div class="alert alert-success">{{ session('message') }}</div>
  @endif

  @forelse($requests as $r)
    <div class="sr-card">
      @if($r->imageUrl())
        <img src="{{ $r->imageUrl() }}" class="sr-thumb" alt="">
      @elseif($r->video_path)
        <div class="sr-thumb" style="background:#0f172a;color:#fff"><i class="fa fa-play"></i></div>
      @else
        <div class="sr-thumb"><i class="fa fa-bullhorn"></i></div>
      @endif
      <div style="flex:1;min-width:0">
        <div class="d-flex justify-content-between align-items-start">
          <h5 class="sr-title">{{ $r->title }}</h5>
          @if(in_array($r->id, $answered))
            <span class="sr-badge done"><i class="fa fa-check"></i> Responded</span>
          @else
            <span class="sr-badge new">New</span>
          @endif
        </div>
        <div class="sr-meta">
          {{ $r->created_at?->diffForHumans() }}
          @if($r->category) · {{ $r->category }} @endif
          @if($r->brand) · {{ $r->brand }} @endif
          · Qty: {{ $r->quantity }}
          @if($r->budgetLabel()) · Budget: {{ $r->budgetLabel() }} @endif
          @if($r->location) · <i class="fa fa-map-marker"></i> {{ $r->location }} @endif
        </div>
        <div class="sr-desc">{{ Str::limit($r->description, 160) }}</div>
        <div style="margin-top:8px">
          <a href="{{ route('vendor.sourcing.show', $r->id) }}" class="sr-btn">
            {{ in_array($r->id, $answered) ? 'Open Chat' : 'View & Respond' }} <i class="fa fa-arrow-right"></i>
          </a>
          @if($r->video_path)<span style="font-size:.75rem;color:#64748b;margin-left:8px"><i class="fa fa-video-camera"></i> Includes video</span>@endif
        </div>
      </div>
    </div>
  @empty
    <div class="sr-card" style="justify-content:center;text-align:center;color:#94a3b8;display:block;padding:40px">
      <i class="fa fa-inbox" style="font-size:2rem;opacity:.4"></i>
      <div style="font-weight:600;color:#475569;margin-top:8px">No open requests right now</div>
      <div style="font-size:.85rem">When customers post product requests, they'll appear here.</div>
    </div>
  @endforelse

  <div>{{ $requests->links() }}</div>
</div>
@endsection
