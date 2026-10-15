@extends('landlord.marketplace.layout')
@section('title', $req->title)

@section('extra-css')
<style>
.thread-list { max-height: 520px; overflow-y:auto; }
.thread-item { display:flex; gap:.6rem; padding:.7rem; border-radius:10px; cursor:pointer; border:1px solid transparent; }
.thread-item:hover { background:#f8fafc; }
.thread-item.active { background:#eff6ff; border-color:#bfdbfe; }
.thread-av { width:40px;height:40px;border-radius:10px;background:var(--mp-primary);color: var(--mp-on-primary);display:flex;align-items:center;justify-content:center;font-weight:800;flex-shrink:0; }
.chat-box { display:flex; flex-direction:column; height:520px; }
.chat-head { padding:.8rem 1rem; border-bottom:1px solid var(--mp-border); display:flex; align-items:center; gap:.6rem; }
.chat-msgs { flex:1; overflow-y:auto; padding:1rem; background:#f8fafc; display:flex; flex-direction:column; gap:.55rem; }
.msg { max-width:78%; padding:.55rem .8rem; border-radius:12px; font-size:var(--text-sm); line-height:1.4; }
.msg.them { background:#fff; border:1px solid var(--mp-border); align-self:flex-start; border-bottom-left-radius:3px; }
.msg.me { background:var(--mp-primary); color: var(--mp-on-primary); align-self:flex-end; border-bottom-right-radius:3px; }
.msg .meta { display:block; font-size:.66rem; opacity:.65; margin-top:.25rem; }
.msg-ref { background:#fff; border:1px solid var(--mp-border); border-radius:10px; padding:.5rem; margin-top:.4rem; display:flex; gap:.5rem; align-items:center; }
.msg-ref img { width:42px;height:42px;object-fit:cover;border-radius:7px; }
.chat-input { display:flex; gap:.5rem; padding:.7rem; border-top:1px solid var(--mp-border); background:#fff; }
.chat-input input { flex:1; border:1.5px solid var(--mp-border); border-radius:9px; padding:.55rem .8rem; font-size:var(--text-sm); outline:none; }
.chat-input input:focus { border-color:var(--mp-primary); }
.chat-empty { flex:1; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:var(--text-sm); text-align:center; padding:2rem; }
</style>
@endsection

@section('content')
<div class="container py-4">
  <a href="{{ route('customer.requests.index') }}" class="text-decoration-none" style="font-size:var(--text-sm)"><i class="fas fa-arrow-left me-1"></i> Back to requests</a>

  {{-- Request summary --}}
  <div class="bg-white rounded-3 p-4 mt-2 mb-3" style="border:1px solid var(--mp-border)">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <h4 class="fw-bold mb-1">{{ $req->title }}</h4>
        <div class="text-muted" style="font-size:var(--text-xs)">Posted {{ $req->created_at?->diffForHumans() }}
          @if($req->category) · {{ $req->category }} @endif
          @if($req->budgetLabel()) · Budget: {{ $req->budgetLabel() }} @endif
          · Qty: {{ $req->quantity }}</div>
      </div>
      <div class="text-end">
        <span class="badge {{ $req->status==='open'?'bg-success':'bg-secondary' }} text-capitalize">{{ $req->status }}</span>
        @if($req->status==='open')
          <form action="{{ route('customer.requests.close', $req->id) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Close this request? Vendors will no longer be able to respond.')">
            @csrf<button class="btn btn-sm btn-outline-secondary ms-1">Close</button>
          </form>
        @endif
      </div>
    </div>
    <p class="mt-3 mb-2" style="font-size:var(--text-sm);color:#475569;white-space:pre-line">{{ $req->description }}</p>
    <div class="d-flex gap-3 flex-wrap mt-2">
      @if($req->imageUrl())
        <a href="{{ $req->imageUrl() }}" target="_blank"><img src="{{ $req->imageUrl() }}" style="height:120px;border-radius:10px;border:1px solid var(--mp-border)" alt=""></a>
      @endif
      @if($req->videoUrl())
        <video src="{{ $req->videoUrl() }}" controls style="height:120px;border-radius:10px;border:1px solid var(--mp-border);background:#000"></video>
      @endif
    </div>
  </div>

  {{-- Responses + chat --}}
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="bg-white rounded-3 p-2" style="border:1px solid var(--mp-border)">
        <div class="fw-bold px-2 py-2" style="font-size:var(--text-sm)">Vendor Responses <span class="badge bg-primary">{{ $threads->count() }}</span></div>
        <div class="thread-list">
          @forelse($threads as $t)
            <div class="thread-item" data-thread="{{ $t->id }}" data-vendor="{{ $t->tenant_name ?: 'Vendor' }}" onclick="selectThread({{ $t->id }}, this)">
              <div class="thread-av">{{ strtoupper(Str::substr($t->tenant_name ?: 'V', 0, 1)) }}</div>
              <div style="min-width:0">
                <div class="fw-semibold" style="font-size:var(--text-sm)">{{ Str::limit($t->tenant_name ?: 'Vendor', 22) }}</div>
                <div class="text-muted" style="font-size:var(--text-xs)">{{ Str::limit($t->message ?: 'Sent an offer', 30) }}</div>
                @if($t->price)<div style="font-size:var(--text-xs);color: var(--mp-link);font-weight:700">{{ $t->currency }} {{ number_format($t->price,2) }}</div>@endif
              </div>
            </div>
          @empty
            <div class="text-center text-muted p-4" style="font-size:var(--text-sm)">
              <i class="fas fa-hourglass-half mb-2 d-block" style="font-size:1.5rem;opacity:.4"></i>
              Waiting for vendors to respond. We'll notify you by email when they do.
            </div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="bg-white rounded-3" style="border:1px solid var(--mp-border)">
        <div class="chat-box">
          <div class="chat-head" id="chatHead" style="display:none">
            <div class="thread-av" id="chatAv"></div>
            <div><div class="fw-bold" style="font-size:var(--text-sm)" id="chatVendor"></div><div class="text-muted" style="font-size:var(--text-xs)">Vendor chat</div></div>
          </div>
          <div class="chat-msgs" id="chatMsgs">
            <div class="chat-empty" id="chatEmpty">
              <div>
                <i class="far fa-comments d-block mb-2" style="font-size:2rem;opacity:.4"></i>
                @if($threads->count()) Select a vendor on the left to view their offer and chat. @else No responses yet — hang tight! @endif
              </div>
            </div>
          </div>
          <form class="chat-input" id="chatForm" style="display:none" onsubmit="return sendMessage(event)">
            <input type="text" id="chatBody" placeholder="Type a message to the vendor…" maxlength="2000" autocomplete="off" required>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let activeThread = null, pollTimer = null;
const msgUrlBase  = "{{ url('account/threads') }}";

function esc(s){ const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }

function renderMessages(list) {
    const box = document.getElementById('chatMsgs');
    box.innerHTML = '';
    if (!list.length) {
        box.innerHTML = '<div class="chat-empty"><div>No messages yet. Say hello or ask about availability & price.</div></div>';
        return;
    }
    list.forEach(m => {
        const wrap = document.createElement('div');
        wrap.className = 'msg ' + (m.mine ? 'me' : 'them');
        let html = esc(m.body);
        if (m.listing) {
            html += `<a href="${m.listing.url}" class="msg-ref text-decoration-none text-dark" target="_blank">
                ${m.listing.image ? `<img src="${m.listing.image}" alt="">` : ''}
                <div style="min-width:0"><div style="font-size:var(--text-xs);font-weight:700">${esc(m.listing.name)}</div>
                <div style="font-size:var(--text-xs);color: var(--mp-link)">${esc(m.listing.price)} · Order now →</div></div></a>`;
        }
        html += `<span class="meta">${esc(m.sender)} · ${esc(m.time)}</span>`;
        wrap.innerHTML = html;
        box.appendChild(wrap);
    });
    box.scrollTop = box.scrollHeight;
}

function loadMessages() {
    if (!activeThread) return;
    fetch(`${msgUrlBase}/${activeThread}/messages`, { headers: { 'X-Requested-With':'XMLHttpRequest' } })
        .then(r => r.json()).then(d => renderMessages(d.messages || []))
        .catch(()=>{});
}

function selectThread(id, el) {
    activeThread = id;
    document.querySelectorAll('.thread-item').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
    const vendor = el ? el.getAttribute('data-vendor') : 'Vendor';
    document.getElementById('chatHead').style.display = 'flex';
    document.getElementById('chatForm').style.display = 'flex';
    document.getElementById('chatVendor').textContent = vendor;
    document.getElementById('chatAv').textContent = (vendor[0]||'V').toUpperCase();
    document.getElementById('chatEmpty')?.remove();
    loadMessages();
    clearInterval(pollTimer);
    pollTimer = setInterval(loadMessages, 4000);
}

function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chatBody');
    const body = input.value.trim();
    if (!body || !activeThread) return false;
    input.value = '';
    fetch(`${msgUrlBase}/${activeThread}/send`, {
        method:'POST',
        headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest' },
        body: JSON.stringify({ body })
    }).then(r => r.json()).then(()=> loadMessages()).catch(()=>{});
    return false;
}

// Auto-open the first thread
document.addEventListener('DOMContentLoaded', () => {
    const first = document.querySelector('.thread-item');
    if (first) selectThread(parseInt(first.getAttribute('data-thread')), first);
});
</script>
@endpush
