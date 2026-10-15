@extends('backend.layout.main')
@section('content')

<style>
.vr-card { background:#fff; border:1px solid #e6e9f0; border-radius:12px; padding:18px; margin-bottom:16px; }
.vr-title { font-weight:700; font-size:1.15rem; color:#1e293b; margin:0 0 4px; }
.vr-meta { font-size:.78rem; color:#94a3b8; }
.vr-desc { font-size:.9rem; color:#475569; white-space:pre-line; margin-top:10px; }
.vr-media { display:flex; gap:12px; flex-wrap:wrap; margin-top:12px; }
.vr-media img, .vr-media video { height:130px; border-radius:10px; border:1px solid #e6e9f0; }
.label { font-size:.8rem; font-weight:600; color:#334155; margin-bottom:4px; display:block; }
.chat-box { display:flex; flex-direction:column; height:460px; border:1px solid #e6e9f0; border-radius:12px; overflow:hidden; background:#fff; }
.chat-msgs { flex:1; overflow-y:auto; padding:14px; background:#f8fafc; display:flex; flex-direction:column; gap:8px; }
.msg { max-width:78%; padding:.5rem .8rem; border-radius:12px; font-size:.86rem; line-height:1.4; }
.msg.them { background:#fff; border:1px solid #e6e9f0; align-self:flex-start; border-bottom-left-radius:3px; }
.msg.me { background:var(--lp-primary,#0066CC); color:#fff; align-self:flex-end; border-bottom-right-radius:3px; }
.msg .meta { display:block; font-size:.66rem; opacity:.65; margin-top:.25rem; }
.msg-ref { background:rgba(255,255,255,.15); border-radius:8px; padding:.3rem .5rem; margin-top:.35rem; font-size:.75rem; }
.chat-input { display:flex; gap:8px; padding:10px; border-top:1px solid #e6e9f0; background:#fff; flex-wrap:wrap; }
.chat-input input[type=text], .chat-input select { border:1.5px solid #e6e9f0; border-radius:8px; padding:.5rem .7rem; font-size:.85rem; }
.chat-input .body { flex:1; min-width:180px; }
.vr-send { background:var(--lp-primary,#0066CC); color:#fff; border:none; border-radius:8px; padding:.5rem .9rem; font-weight:600; }
</style>

<div style="padding:6px 4px">
  <a href="{{ route('vendor.sourcing.index') }}" style="font-size:.84rem"><i class="fa fa-arrow-left"></i> Back to requests</a>

  @if(session('message'))<div class="alert alert-success mt-2">{{ session('message') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger mt-2">{{ $errors->first() }}</div>@endif

  {{-- Request summary --}}
  <div class="vr-card mt-2">
    <div class="d-flex justify-content-between align-items-start flex-wrap">
      <div>
        <h4 class="vr-title">{{ $req->title }}</h4>
        <div class="vr-meta">
          {{ $req->created_at?->diffForHumans() }}
          @if($req->category) · {{ $req->category }} @endif
          @if($req->brand) · {{ $req->brand }} @endif
          · Qty: {{ $req->quantity }}
          @if($req->budgetLabel()) · Budget: {{ $req->budgetLabel() }} @endif
          @if($req->location) · <i class="fa fa-map-marker"></i> {{ $req->location }} @endif
        </div>
      </div>
      <span class="badge {{ $req->status==='open'?'badge-success':'badge-secondary' }} text-capitalize">{{ $req->status }}</span>
    </div>
    <div class="vr-desc">{{ $req->description }}</div>
    <div class="vr-media">
      @if($req->imageUrl())<a href="{{ $req->imageUrl() }}" target="_blank"><img src="{{ $req->imageUrl() }}" alt=""></a>@endif
      @if($req->videoUrl())<video src="{{ $req->videoUrl() }}" controls style="background:#000"></video>@endif
    </div>
  </div>

  @if(!$thread)
    {{-- First response --}}
    <div class="vr-card">
      <h5 style="font-weight:700">Send your response</h5>
      <p style="font-size:.85rem;color:#64748b">Tell the customer what you have and, optionally, reference a product from your store so they can order it directly.</p>
      <form action="{{ route('vendor.sourcing.respond', $req->id) }}" method="POST">
        @csrf
        <div class="form-group">
          <label class="label">Message *</label>
          <textarea name="message" class="form-control" rows="3" maxlength="2000" required placeholder="e.g. Yes, we have this part in stock. Here's the exact item…"></textarea>
        </div>
        <div class="row">
          <div class="col-md-4 form-group">
            <label class="label">Your price (optional)</label>
            <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00">
          </div>
          <div class="col-md-8 form-group">
            <label class="label">Reference a product (optional)</label>
            <select name="listing_id" class="form-control">
              <option value="">— None —</option>
              @foreach($listings as $l)
                <option value="{{ $l->id }}">{{ Str::limit($l->name, 50) }} ({{ $l->currency }} {{ number_format($l->promo_price ?: $l->price, 2) }})</option>
              @endforeach
            </select>
          </div>
        </div>
        <button type="submit" class="vr-send"><i class="fa fa-paper-plane"></i> Send response</button>
      </form>
    </div>
  @else
    {{-- Chat --}}
    <div class="chat-box" data-thread="{{ $thread->id }}">
      <div class="chat-msgs" id="chatMsgs"></div>
      <form class="chat-input" id="chatForm" onsubmit="return vrSend(event)">
        <select id="chatRef" title="Attach a product">
          <option value="">Attach product…</option>
          @foreach($listings as $l)
            <option value="{{ $l->id }}">{{ Str::limit($l->name, 40) }}</option>
          @endforeach
        </select>
        <input type="text" id="chatBody" class="body" placeholder="Type a message to the customer…" maxlength="2000" autocomplete="off" required>
        <button type="submit" class="vr-send"><i class="fa fa-paper-plane"></i></button>
      </form>
    </div>
  @endif
</div>
@endsection

@if($thread)
@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const threadId = {{ $thread->id }};
const msgUrl  = "{{ url('marketplace-requests/threads') }}/" + threadId + "/messages";
const sendUrl = "{{ url('marketplace-requests/threads') }}/" + threadId + "/send";

function esc(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }

function render(list) {
    const box = document.getElementById('chatMsgs');
    box.innerHTML = '';
    list.forEach(m => {
        const w = document.createElement('div');
        w.className = 'msg ' + (m.mine ? 'me' : 'them');
        let html = esc(m.body);
        if (m.listing) html += `<div class="msg-ref"><i class="fa fa-box"></i> ${esc(m.listing.name)} — ${esc(m.listing.price)}</div>`;
        html += `<span class="meta">${esc(m.sender)} · ${esc(m.time)}</span>`;
        w.innerHTML = html;
        box.appendChild(w);
    });
    box.scrollTop = box.scrollHeight;
}
function load() {
    fetch(msgUrl, { headers:{ 'X-Requested-With':'XMLHttpRequest' } })
        .then(r => r.json()).then(d => render(d.messages || [])).catch(()=>{});
}
function vrSend(e) {
    e.preventDefault();
    const body = document.getElementById('chatBody');
    const ref  = document.getElementById('chatRef');
    const text = body.value.trim();
    if (!text) return false;
    const payload = { body: text, listing_id: ref.value || null };
    body.value = '';
    fetch(sendUrl, {
        method:'POST',
        headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest' },
        body: JSON.stringify(payload)
    }).then(r => r.json()).then(()=>{ ref.value=''; load(); }).catch(()=>{});
    return false;
}
load();
setInterval(load, 4000);
</script>
@endpush
@endif
