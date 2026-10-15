{{-- Order tracking timeline. Requires $order (MarketplaceOrder). --}}
@php $steps = $order->trackingSteps(); @endphp
<style>
.trk { display:flex; flex-direction:column; gap:0; }
.trk-row { display:flex; gap:14px; }
.trk-rail { display:flex; flex-direction:column; align-items:center; }
.trk-dot { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:var(--text-sm); flex-shrink:0; z-index:1; }
.trk-dot.done { background:var(--mp-link,#164EA6); color:#fff; }
.trk-dot.current { background:var(--mp-link,#164EA6); color:#fff; box-shadow:0 0 0 4px color-mix(in srgb, var(--mp-link,#164EA6) 20%, transparent); }
.trk-dot.upcoming { background:#e2e8f0; color:#94a3b8; }
.trk-dot.cancelled { background:#ef4444; color:#fff; }
.trk-line { width:2px; flex:1; background:#e2e8f0; min-height:22px; }
.trk-line.done { background:var(--mp-primary,#0066CC); }
.trk-body { padding-bottom:20px; }
.trk-row:last-child .trk-body { padding-bottom:0; }
.trk-label { font-weight:700; font-size:var(--text-sm); color:#0f172a; }
.trk-label.upcoming { color:#94a3b8; font-weight:600; }
.trk-at { font-size:var(--text-xs); color:#94a3b8; margin-top:2px; }
</style>
<div class="trk">
  @foreach($steps as $i => $s)
    <div class="trk-row">
      <div class="trk-rail">
        <div class="trk-dot {{ $s['state'] }}"><i class="fas {{ $s['icon'] }}"></i></div>
        @if(!$loop->last)<div class="trk-line {{ $s['state']==='done' ? 'done' : '' }}"></div>@endif
      </div>
      <div class="trk-body">
        <div class="trk-label {{ $s['state']==='upcoming' ? 'upcoming' : '' }}">{{ $s['label'] }}</div>
        @if($s['at'])<div class="trk-at">{{ $s['at'] }}</div>@endif
        @if($s['state']==='current')<div class="trk-at" style="color: var(--mp-link);font-weight:600">In progress</div>@endif
      </div>
    </div>
  @endforeach
</div>
@if($order->tracking_number)
  <div class="mt-3 p-2 rounded-3" style="background:#f8fafc;border:1px solid var(--mp-border);font-size:var(--text-sm)">
    <i class="fas fa-barcode me-1" style="color: var(--mp-link)"></i> Tracking #: <strong>{{ $order->tracking_number }}</strong>
    @if($order->delivery_agency_name) · via {{ $order->delivery_agency_name }}@endif
  </div>
@endif
