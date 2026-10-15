{{-- Shared form partial for create & edit --}}
@php $pkg = $package ?? null; @endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Package Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $pkg?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Badge Label</label>
        <input type="text" name="badge" class="form-control" placeholder="Most Popular"
               value="{{ old('badge', $pkg?->badge) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Sort Order</label>
        <input type="number" name="sort_order" class="form-control" min="0"
               value="{{ old('sort_order', $pkg?->sort_order ?? 0) }}">
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description', $pkg?->description) }}</textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label">Monthly Price ($) <span class="text-danger">*</span></label>
        <input type="number" name="price_monthly" class="form-control" step="0.01" min="0" required
               value="{{ old('price_monthly', $pkg?->price_monthly ?? 0) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Yearly Price ($) <span class="text-danger">*</span></label>
        <input type="number" name="price_yearly" class="form-control" step="0.01" min="0" required
               value="{{ old('price_yearly', $pkg?->price_yearly ?? 0) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">One-Time / Lifetime Price ($)
            <small class="text-muted d-block" style="font-size:.75rem;">Leave blank to not offer a lifetime option</small>
        </label>
        <input type="number" name="price_onetime" class="form-control" step="0.01" min="0"
               placeholder="e.g. 600"
               value="{{ old('price_onetime', $pkg?->price_onetime) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="is_active" class="form-select">
            <option value="1" {{ old('is_active', $pkg?->is_active ?? 1) == 1 ? 'selected' : '' }}>Active</option>
            <option value="0" {{ old('is_active', $pkg?->is_active ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
        </select>
    </div>

    <div class="col-md-5">
        <label class="form-label d-block">Public Visibility</label>
        <div class="form-check form-switch mt-1">
            <input class="form-check-input" type="checkbox" name="show_on_public" id="show_on_public" value="1"
                   {{ old('show_on_public', $pkg?->show_on_public ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="show_on_public">
                Show on public pages
                <small class="text-muted fw-normal d-block" style="font-size:.75rem;">
                    Landing page &amp; pricing/compare page. Uncheck for private/internal plans only.
                </small>
            </label>
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Product Limit</label>
        <input type="number" name="product_limit" class="form-control" min="1" placeholder="Leave blank = unlimited"
               value="{{ old('product_limit', $pkg?->product_limit) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">User Limit</label>
        <input type="number" name="user_limit" class="form-control" min="1" placeholder="Leave blank = unlimited"
               value="{{ old('user_limit', $pkg?->user_limit) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Warehouse Limit</label>
        <input type="number" name="warehouse_limit" class="form-control" min="1" placeholder="Leave blank = unlimited"
               value="{{ old('warehouse_limit', $pkg?->warehouse_limit) }}">
    </div>

    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input class="form-check-input" type="checkbox" name="is_free_trial" id="is_free_trial" value="1"
                   {{ old('is_free_trial', $pkg?->is_free_trial) ? 'checked' : '' }}
                   onchange="toggleTrialFields()">
            <label class="form-check-label" for="is_free_trial">
                This is a Free Trial Plan
                <small class="text-muted d-block" style="font-size:.72rem;">Entire plan = free, no payment</small>
            </label>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input class="form-check-input" type="checkbox" name="allow_paid_trial" id="allow_paid_trial" value="1"
                   {{ old('allow_paid_trial', $pkg?->allow_paid_trial) ? 'checked' : '' }}
                   onchange="toggleTrialFields()">
            <label class="form-check-label" for="allow_paid_trial">
                Allow Trial on Paid Plan
                <small class="text-muted d-block" style="font-size:.72rem;">Tenants can try before paying</small>
            </label>
        </div>
    </div>

    <div class="col-md-4" id="trial_days_wrap"
         style="display:{{ (old('is_free_trial', $pkg?->is_free_trial) || old('allow_paid_trial', $pkg?->allow_paid_trial)) ? 'block' : 'none' }}">
        <label class="form-label">Trial Days</label>
        <input type="number" name="trial_days" class="form-control" min="1" max="365"
               value="{{ old('trial_days', $pkg?->trial_days ?? 14) }}">
    </div>
</div>

<script>
function toggleTrialFields() {
    var freeTrial  = document.getElementById('is_free_trial').checked;
    var paidTrial  = document.getElementById('allow_paid_trial').checked;
    document.getElementById('trial_days_wrap').style.display = (freeTrial || paidTrial) ? 'block' : 'none';
}
</script>

<!-- Feature Matrix -->
<div class="mt-4">
    <label class="form-label fw-semibold">Features</label>
    <div class="border rounded p-3" style="background:#f8fafc;">
        <div class="row g-2">
            @foreach($features as $key => $label)
            <div class="col-md-4 col-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="features[]"
                           id="feat_{{ $key }}" value="{{ $key }}"
                           {{ in_array($key, old('features', $pkg?->features ?? [])) ? 'checked' : '' }}>
                    <label class="form-check-label" for="feat_{{ $key }}" style="font-size:.875rem;">
                        {{ is_array($label) ? $label['name'] : $label }}
                    </label>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-2">
            <button type="button" class="btn btn-xs btn-link p-0 me-3"
                    onclick="document.querySelectorAll('[name=\'features[]\']').forEach(el=>el.checked=true)">
                Check All
            </button>
            <button type="button" class="btn btn-xs btn-link p-0 text-danger"
                    onclick="document.querySelectorAll('[name=\'features[]\']').forEach(el=>el.checked=false)">
                Uncheck All
            </button>
        </div>
    </div>
</div>
