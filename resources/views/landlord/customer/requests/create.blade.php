@extends('landlord.marketplace.layout')
@section('title', 'New Sourcing Request')

@section('content')
<div class="container py-4">
  <div class="acct-shell">
    @include('landlord.customer._nav', ['active' => 'requests'])
    <div class="acct-main">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-3">
        <div>
          <h4 class="fw-bold mb-1">Post a sourcing request</h4>
          <p class="text-muted mb-0" style="font-size:var(--text-sm)">Describe what you need and let marketplace vendors respond with offers, availability, and pricing.</p>
        </div>
        <a href="{{ route('customer.requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
          <i class="fas fa-arrow-left me-1"></i> Back to requests
        </a>
      </div>

      @if($errors->any())<div class="mp-flash mp-flash-error"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>@endif

      <div class="row g-4 align-items-start">
        <div class="col-lg-4">
          <div class="bg-white rounded-4 p-4 shadow-sm" style="border:1px solid var(--mp-border)">
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:42px;height:42px;background:rgba(0,102,204,.12);color: var(--mp-link)">
                <i class="fas fa-bullhorn"></i>
              </div>
              <div>
                <div class="fw-bold" style="font-size:var(--text-base)">How it works</div>
                <div class="text-muted" style="font-size:var(--text-xs)">Fast, clear, and visible to vendors</div>
              </div>
            </div>
            <ul class="list-unstyled mb-0" style="font-size:var(--text-sm);color:#475569">
              <li class="d-flex gap-2 mb-2"><i class="fas fa-check-circle mt-1" style="color: var(--mp-link)"></i> Share the product, part, or service you need.</li>
              <li class="d-flex gap-2 mb-2"><i class="fas fa-check-circle mt-1" style="color: var(--mp-link)"></i> Add photos or videos to help vendors understand the request.</li>
              <li class="d-flex gap-2"><i class="fas fa-check-circle mt-1" style="color: var(--mp-link)"></i> Receive offers and compare pricing from multiple vendors.</li>
            </ul>
          </div>
        </div>

        <div class="col-lg-8">
          <form action="{{ route('customer.requests.store') }}" method="POST" enctype="multipart/form-data"
                class="bg-white rounded-4 p-4 p-md-5 shadow-sm" style="border:1px solid var(--mp-border)" id="reqForm">
            @csrf
            <div class="mb-4">
              <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" maxlength="150" required
                     placeholder="e.g. Water pump impeller for Honda generator EU30i" value="{{ old('title') }}">
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Description <span class="text-danger">*</span></label>
              <textarea name="description" class="form-control" rows="5" maxlength="3000" required
                        style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem"
                        placeholder="Describe exactly what you need — model, size, colour, part number, condition, etc.">{{ old('description') }}</textarea>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Category</label>
                <input type="text" name="category" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" list="catList" value="{{ old('category') }}" placeholder="Optional">
                <datalist id="catList">@foreach($categories as $cat)<option value="{{ $cat }}">@endforeach</datalist>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Brand</label>
                <input type="text" name="brand" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" value="{{ old('brand') }}" placeholder="Optional">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Quantity</label>
                <input type="number" name="quantity" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" min="1" value="{{ old('quantity',1) }}">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Budget from</label>
                <input type="number" step="0.01" name="budget_min" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" value="{{ old('budget_min') }}" placeholder="Optional">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Budget to</label>
                <input type="number" step="0.01" name="budget_max" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" value="{{ old('budget_max') }}" placeholder="Optional">
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:var(--text-sm)">Your location</label>
                <input type="text" name="location" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" value="{{ old('location') }}" placeholder="City / area (helps vendors near you respond)">
              </div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:var(--text-sm)"><i class="fas fa-image me-1"></i> Photo <span class="text-muted" style="font-weight:400">(optional)</span></label>
                <input type="file" name="image" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" accept="image/*">
                <div class="form-text" style="font-size:var(--text-xs)">JPG/PNG/WebP, up to 5&nbsp;MB.</div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:var(--text-sm)"><i class="fas fa-video me-1"></i> Showcase video <span class="text-muted" style="font-weight:400">(optional)</span></label>
                <input type="file" name="video" id="videoInput" class="form-control" style="border-radius:10px;border-color:#dbe4f0;padding:.72rem .85rem" accept="video/mp4,video/webm,video/quicktime">
                <div class="form-text" style="font-size:var(--text-xs)">MP4/WebM/MOV, max ~2&nbsp;minutes (80&nbsp;MB). Great for showing the exact part you already have.</div>
                <div id="vidWarn" class="text-danger mt-1" style="font-size:var(--text-xs);display:none"></div>
              </div>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 pt-3" style="border-top:1px solid var(--mp-border)">
              <div class="text-muted" style="font-size:var(--text-xs)">Fields marked with <span class="text-danger">*</span> are required.</div>
              <button type="submit" class="btn btn-primary rounded-pill px-4" id="reqSubmit">
                <i class="fas fa-paper-plane me-1"></i> Send to all vendors
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('videoInput');
    const warn  = document.getElementById('vidWarn');
    const submit = document.getElementById('reqSubmit');
    if (!input) return;
    input.addEventListener('change', function () {
        warn.style.display = 'none'; submit.disabled = false;
        const f = input.files[0];
        if (!f) return;
        if (f.size > 80 * 1024 * 1024) {
            warn.textContent = 'That video is larger than 80 MB. Please trim it or lower the quality.';
            warn.style.display = 'block'; submit.disabled = true; input.value = ''; return;
        }
        const v = document.createElement('video');
        v.preload = 'metadata';
        v.onloadedmetadata = function () {
            window.URL.revokeObjectURL(v.src);
            if (v.duration > 130) {
                warn.textContent = 'Please keep the video under 2 minutes (' + Math.round(v.duration) + 's selected).';
                warn.style.display = 'block'; submit.disabled = true; input.value = '';
            }
        };
        v.src = URL.createObjectURL(f);
    });
})();
</script>
@endpush
