@extends('superadmin.layout.main')
@section('title', 'Promotions')
@section('page-title', 'Plan Promotions')

@section('content')

@if(!empty($tableMissing))
    <div class="alert alert-warning">
        <strong>Setup needed:</strong> the <code>plan_promotions</code> table doesn't exist on the landlord database yet.
        Run <code>php artisan landlord:migrate</code> on the server (this migration lives in
        <code>database/migrations/landlord/</code> and does <em>not</em> run with plain <code>php artisan migrate</code>).
    </div>
@endif

@if($current)
<div class="alert alert-success d-flex align-items-center mb-4">
    <i class="fas fa-bolt me-2"></i>
    <div>
        <strong>Live now:</strong> “{{ $current->title }}” — {{ $current->label() }} on all plans,
        until {{ $current->ends_at->format('M j, Y g:i A') }}. Discounted prices and the pop-up are showing on the landing page.
    </div>
</div>
@endif

<div class="row g-4">
    {{-- ── Create / edit form ─────────────────────────────────────────── --}}
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold" id="form-title">New Promotion</span>
                <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="cancel-edit"
                        onclick="resetPromoForm()"><i class="fas fa-times me-1"></i>Cancel</button>
            </div>
            <div class="card-body">
                <form method="POST" id="promo-form" action="{{ route('superadmin.promotions.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="promo-method" value="POST">

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="f-title" class="form-control" maxlength="120"
                               placeholder="e.g. Summer Season Sale" required>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label">Discount type <span class="text-danger">*</span></label>
                            <select name="discount_type" id="f-type" class="form-select" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="flat">Flat amount ($)</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Discount value <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="discount_value" id="f-value"
                                   class="form-control" placeholder="25" required>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label">Starts at <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="starts_at" id="f-starts" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Ends at <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="ends_at" id="f-ends" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Badge text <span class="text-muted">(optional)</span></label>
                        <input type="text" name="badge_text" id="f-badge" class="form-control" maxlength="40"
                               placeholder="Leave blank to auto-generate, e.g. “-25% OFF”">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pop-up message <span class="text-muted">(optional)</span></label>
                        <textarea name="description" id="f-desc" class="form-control" rows="3"
                                  placeholder="Shown in the landing-page modal. e.g. Save big this season on every plan!"></textarea>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="show_modal" id="f-modal" value="1" checked>
                        <label class="form-check-label" for="f-modal">Show pop-up modal on landing page</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="f-active" value="1" checked>
                        <label class="form-check-label" for="f-active">Active</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i><span id="submit-label">Create Promotion</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── List ───────────────────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-3 fw-semibold">All Promotions</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Promotion</th>
                            <th>Discount</th>
                            <th>Window</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotions as $p)
                        @php $live = $p->isLive(); @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $p->title }}</div>
                                @if($p->badge_text)<span class="badge bg-warning text-dark">{{ $p->badge_text }}</span>@endif
                            </td>
                            <td>{{ $p->label() }}</td>
                            <td class="small text-muted">
                                {{ $p->starts_at->format('M j, Y') }}<br>→ {{ $p->ends_at->format('M j, Y') }}
                            </td>
                            <td>
                                @if($live)
                                    <span class="badge bg-success">Live</span>
                                @elseif(!$p->is_active)
                                    <span class="badge bg-secondary">Inactive</span>
                                @elseif($p->ends_at->isPast())
                                    <span class="badge bg-dark">Expired</span>
                                @else
                                    <span class="badge bg-info text-dark">Scheduled</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @php
                                    $promoData = [
                                        'id'             => $p->id,
                                        'title'          => $p->title,
                                        'badge_text'     => $p->badge_text,
                                        'description'    => $p->description,
                                        'discount_type'  => $p->discount_type,
                                        'discount_value' => $p->discount_value,
                                        'starts_at'      => optional($p->starts_at)->format('Y-m-d\TH:i'),
                                        'ends_at'        => optional($p->ends_at)->format('Y-m-d\TH:i'),
                                        'show_modal'     => $p->show_modal,
                                        'is_active'      => $p->is_active,
                                    ];
                                @endphp
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="editPromo({{ \Illuminate\Support\Js::from($promoData) }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('superadmin.promotions.toggle', $p->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary" title="Toggle active">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.promotions.destroy', $p->id) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this promotion?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-tags mb-2 d-block" style="font-size:1.6rem;opacity:.4"></i>
                            No promotions yet. Create one to run a season-wide discount.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger mt-3">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<script>
function editPromo(p) {
    document.getElementById('promo-form').action = '{{ url("superadmin/promotions") }}/' + p.id;
    document.getElementById('promo-method').value = 'PUT';
    document.getElementById('form-title').textContent = 'Edit Promotion';
    document.getElementById('submit-label').textContent = 'Update Promotion';
    document.getElementById('cancel-edit').classList.remove('d-none');
    document.getElementById('f-title').value = p.title || '';
    document.getElementById('f-type').value = p.discount_type || 'percentage';
    document.getElementById('f-value').value = p.discount_value || 0;
    document.getElementById('f-starts').value = p.starts_at || '';
    document.getElementById('f-ends').value = p.ends_at || '';
    document.getElementById('f-badge').value = p.badge_text || '';
    document.getElementById('f-desc').value = p.description || '';
    document.getElementById('f-modal').checked = !!p.show_modal;
    document.getElementById('f-active').checked = !!p.is_active;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
window.editPromo = editPromo;
function resetPromoForm() {
    const f = document.getElementById('promo-form');
    f.reset();
    f.action = '{{ route("superadmin.promotions.store") }}';
    document.getElementById('promo-method').value = 'POST';
    document.getElementById('form-title').textContent = 'New Promotion';
    document.getElementById('submit-label').textContent = 'Create Promotion';
    document.getElementById('cancel-edit').classList.add('d-none');
}
window.resetPromoForm = resetPromoForm;
</script>
@endsection
