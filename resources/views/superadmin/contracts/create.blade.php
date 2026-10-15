@extends('superadmin.layout.main')
@section('title', 'New Contract / SLA')
@section('page-title', 'Generate Contract or SLA')

@section('content')

@if(session('not_permitted'))
    <div class="alert alert-danger">{{ session('not_permitted') }}</div>
@endif

<div class="d-flex gap-2 mb-3">
    <a href="{{ route('superadmin.contracts.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<form action="{{ route('superadmin.contracts.store') }}" method="POST" enctype="multipart/form-data">
@csrf
<div class="row g-3">

    {{-- ── Left: contract fields ── --}}
    <div class="col-lg-8">

        {{-- SECTION 1: Basic Info --}}
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-file-contract me-2 text-primary"></i>Contract Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tenant / Client <span class="text-danger">*</span></label>
                        <select name="tenant_id" id="tenantSelect" class="form-select @error('tenant_id') is-invalid @enderror" required>
                            <option value="">— Select tenant —</option>
                            @foreach($tenants as $t)
                                <option value="{{ $t->id }}"
                                    data-company="{{ $t->company_name }}"
                                    data-email="{{ $t->email }}"
                                    {{ old('tenant_id', $selected?->id) == $t->id ? 'selected' : '' }}>
                                    {{ $t->company_name ?? $t->id }} ({{ $t->id }})
                                </option>
                            @endforeach
                        </select>
                        @error('tenant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select name="document_type" id="docType" class="form-select @error('document_type') is-invalid @enderror" required onchange="toggleDocSections()">
                            <option value="full_contract" {{ old('document_type','full_contract') == 'full_contract' ? 'selected' : '' }}>
                                Services Contract Agreement (Full)
                            </option>
                            <option value="sla_only" {{ old('document_type') == 'sla_only' ? 'selected' : '' }}>
                                SLA Only
                            </option>
                            <option value="sow_only" {{ old('document_type') == 'sow_only' ? 'selected' : '' }}>
                                Scope of Work Only
                            </option>
                        </select>
                        @error('document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control" required
                               value="{{ old('effective_date', date('Y-m-d')) }}">
                    </div>

                    <div class="col-md-4" id="amountWrap">
                        <label class="form-label">Contract Amount (USD)</label>
                        <input type="number" name="contract_amount" class="form-control" step="0.01" min="0"
                               placeholder="e.g. 600"
                               value="{{ old('contract_amount') }}">
                    </div>

                    <div class="col-md-2" id="depositWrap">
                        <label class="form-label">Deposit %</label>
                        <input type="number" name="deposit_percent" class="form-control" min="0" max="100"
                               value="{{ old('deposit_percent', 80) }}">
                    </div>

                    <div class="col-md-2" id="balanceWrap">
                        <label class="form-label">Balance %</label>
                        <input type="number" name="balance_percent" class="form-control" min="0" max="100"
                               value="{{ old('balance_percent', 20) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: Timeline & Terms --}}
        <div class="card mb-3" id="termsCard">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-calendar-alt me-2 text-info"></i>Timeline & Service Terms</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Timeline (days)</label>
                        <input type="number" name="timeline_days" class="form-control" min="1"
                               value="{{ old('timeline_days', 90) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Hosting (months)</label>
                        <input type="number" name="hosting_months" class="form-control" min="1"
                               value="{{ old('hosting_months', 12) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Warranty (days)</label>
                        <input type="number" name="warranty_days" class="form-control" min="1"
                               value="{{ old('warranty_days', 90) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Support (months)</label>
                        <input type="number" name="support_months" class="form-control" min="1"
                               value="{{ old('support_months', 3) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 3: Scope of Work --}}
        <div class="card mb-3" id="scopeCard">
            <div class="card-header py-2 fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list-check me-2 text-warning"></i>Scope of Work</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="include_scope" id="includeScope" value="1"
                           {{ old('include_scope', '1') == '1' ? 'checked' : '' }}
                           onchange="document.getElementById('scopeItems').style.display=this.checked?'block':'none'">
                    <label class="form-check-label" for="includeScope" style="font-size:.82rem;">Include in document</label>
                </div>
            </div>
            <div class="card-body" id="scopeItems" style="{{ old('include_scope','1') == '1' ? '' : 'display:none' }}">
                <p class="text-muted" style="font-size:.82rem;">Check all applicable scope items. Add custom items at the bottom for work outside the standard system.</p>
                <div id="scopeList">
                    @foreach($defaultScopeItems as $i => $item)
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="scope_items[]"
                               id="scope_{{ $i }}" value="{{ $item }}"
                               {{ in_array($item, old('scope_items', $defaultScopeItems)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="scope_{{ $i }}" style="font-size:.875rem;">{{ $item }}</label>
                    </div>
                    @endforeach
                </div>

                <hr class="my-3">
                <div class="fw-semibold mb-2" style="font-size:.82rem;">Custom / Additional Scope Items
                    <small class="text-muted fw-normal">(for needs beyond the standard system)</small>
                </div>
                <div id="customScopeItems">
                    @foreach(old('scope_items', []) as $item)
                        @if(!in_array($item, $defaultScopeItems))
                        <div class="input-group mb-2 custom-scope-row">
                            <input type="text" name="scope_items[]" class="form-control" value="{{ $item }}" placeholder="Custom scope item">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.custom-scope-row').remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        @endif
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addCustomScope()">
                    <i class="fas fa-plus me-1"></i>Add Custom Item
                </button>
            </div>
        </div>

        {{-- SECTION 4: Purpose & Description --}}
        <div class="card mb-3" id="purposeCard">
            <div class="card-header py-2 fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="fas fa-align-left me-2 text-primary"></i>Purpose & Software Description</span>
                <small class="text-muted fw-normal">Updates live when you change tenant or system name</small>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">System / Product Name</label>
                    <input type="text" name="system_name" id="systemName" class="form-control"
                           placeholder="e.g. NovaxPOS, NovaxSuite HR"
                           value="{{ old('system_name', 'NovaxPOS') }}">
                    <small class="text-muted">Changing this updates both paragraphs below automatically</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Purpose Paragraph</label>
                    <textarea name="purpose_text" id="purposeText" class="form-control" rows="4">{{ old('purpose_text') }}</textarea>
                </div>
                <div>
                    <label class="form-label fw-semibold">Software Description Paragraph</label>
                    <textarea name="software_description" id="softwareDesc" class="form-control" rows="4">{{ old('software_description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- SECTION 5: SLA Metrics --}}
        <div class="card mb-3" id="slaCard">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-shield-alt me-2 text-info"></i>SLA Metrics</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">System Uptime Target</label>
                        <div class="input-group">
                            <input type="text" name="sla_uptime" class="form-control"
                                   value="{{ old('sla_uptime', '99.5%') }}" placeholder="99.5%">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Critical Issue Response</label>
                        <div class="input-group">
                            <input type="number" name="sla_critical_hours" class="form-control" min="1"
                                   value="{{ old('sla_critical_hours', 48) }}">
                            <span class="input-group-text">hrs</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Bug Fix Turnaround</label>
                        <div class="input-group">
                            <input type="number" name="sla_bugfix_days" class="form-control" min="1"
                                   value="{{ old('sla_bugfix_days', 5) }}">
                            <span class="input-group-text">days</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Maintenance Notice</label>
                        <div class="input-group">
                            <input type="number" name="sla_maintenance_notice_hours" class="form-control" min="1"
                                   value="{{ old('sla_maintenance_notice_hours', 24) }}">
                            <span class="input-group-text">hrs</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Backup Retention</label>
                        <div class="input-group">
                            <input type="number" name="sla_backup_days" class="form-control" min="1"
                                   value="{{ old('sla_backup_days', 30) }}">
                            <span class="input-group-text">days</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Termination Notice</label>
                        <div class="input-group">
                            <input type="number" name="termination_notice_days" class="form-control" min="1"
                                   value="{{ old('termination_notice_days', 30) }}">
                            <span class="input-group-text">days</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Support Hours</label>
                        <input type="text" name="sla_support_hours" class="form-control"
                               value="{{ old('sla_support_hours', 'Monday–Friday, 8 AM–6 PM (Liberia time)') }}"
                               placeholder="e.g. Monday–Friday, 8 AM–6 PM (Liberia time)">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Governing Law (Country / Jurisdiction)</label>
                        <input type="text" name="governing_law" class="form-control"
                               value="{{ old('governing_law', 'Republic of Liberia') }}"
                               placeholder="e.g. Republic of Liberia">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 6: Company Logo --}}
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold">
                <i class="fas fa-image me-2 text-warning"></i>Company Logo
                <small class="text-muted fw-normal ms-2">Appears on the left side of the PDF header</small>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                        <label class="form-label">Upload Company Logo</label>
                        <input type="file" name="company_logo" id="companyLogoInput"
                               class="form-control" accept="image/png,image/jpg,image/jpeg,image/webp"
                               onchange="previewLogo(this)">
                        <small class="text-muted">PNG, JPG or WEBP · max 2 MB · recommended size 300×100px</small>
                    </div>
                    <div class="col-md-5 text-center">
                        <div id="logoPreviewWrap" style="display:none; border:1px dashed #dee2e6; border-radius:8px; padding:12px; background:#f8fafc;">
                            <img id="logoPreview" src="" alt="Preview" style="max-height:60px; max-width:180px; object-fit:contain;">
                            <div class="mt-1" style="font-size:.75rem; color:#64748b;">Preview</div>
                        </div>
                    </div>
                </div>

                @if(!empty($lastLogo) && file_exists(storage_path('app/contract-logos/' . $lastLogo)))
                <div class="mt-3 p-3" style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px;">
                    <div class="d-flex align-items-center gap-3">
                        <img src="data:image/{{ pathinfo($lastLogo, PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/contract-logos/' . $lastLogo))) }}"
                             alt="Last used logo" style="max-height:48px; max-width:120px; object-fit:contain; border-radius:4px; border:1px solid #e2e8f0; background:#fff; padding:4px;">
                        <div>
                            <div class="fw-semibold" style="font-size:.875rem;">Last used company logo</div>
                            <div style="font-size:.78rem; color:#64748b;">{{ $lastLogo }}</div>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="reuse_company_logo" value="1"
                                       id="reuseLogoCheck" {{ old('reuse_company_logo') ? 'checked' : '' }}>
                                <label class="form-check-label" for="reuseLogoCheck" style="font-size:.8125rem;">
                                    Reuse this logo (skip upload above)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- SECTION 7: Notes --}}
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-sticky-note me-2 text-secondary"></i>Internal Notes</div>
            <div class="card-body">
                <textarea name="notes" class="form-control" rows="3"
                          placeholder="Internal notes (not shown on contract)...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-file-pdf me-2"></i>Generate Contract & PDF
        </button>
    </div>

    {{-- ── Right: Preview Card ── --}}
    <div class="col-lg-4">
        <div class="card sticky-top" style="top:80px;">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-info-circle me-2"></i>Contractor Info</div>
            <div class="card-body" style="font-size:.875rem;">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Company</dt>
                    <dd class="col-7">{{ $settings['site_name'] ?? 'BridgeNovaX Technologies' }}</dd>

                    <dt class="col-5 text-muted">CEO</dt>
                    <dd class="col-7">Omaru A. Kamara</dd>

                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7" style="word-break:break-all;">{{ $settings['mail_from_address'] ?? config('mail.from.address') }}</dd>

                    <dt class="col-5 text-muted">Country</dt>
                    <dd class="col-7">Liberia</dd>
                </dl>
            </div>

            <div class="card-header py-2 fw-semibold"><i class="fas fa-user me-2"></i>Selected Tenant</div>
            <div class="card-body" style="font-size:.875rem;" id="tenantPreview">
                <span class="text-muted">Select a tenant above</span>
            </div>

            <div class="card-header py-2 fw-semibold"><i class="fas fa-lightbulb me-2"></i>Document Guide</div>
            <div class="card-body" style="font-size:.82rem;color:#64748b;">
                <p class="mb-2"><strong class="text-primary">Full Contract</strong> — for new clients subscribing to any paid plan. Includes purpose, SoW, payment terms, SLA, warranty, IP rights, and signatures.</p>
                <p class="mb-2"><strong class="text-info">SLA Only</strong> — for existing clients who need a standalone Service Level Agreement without the full contract.</p>
                <p class="mb-0"><strong class="text-warning">Scope of Work Only</strong> — for custom development requests beyond the standard system features.</p>
            </div>
        </div>
    </div>

</div>
</form>

<script>
function addCustomScope() {
    const container = document.getElementById('customScopeItems');
    const row = document.createElement('div');
    row.className = 'input-group mb-2 custom-scope-row';
    row.innerHTML = `<input type="text" name="scope_items[]" class="form-control" placeholder="Custom scope item">
                     <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.custom-scope-row').remove()">
                         <i class="fas fa-times"></i>
                     </button>`;
    container.appendChild(row);
    row.querySelector('input').focus();
}

function toggleDocSections() {
    const type = document.getElementById('docType').value;
    const termsCard   = document.getElementById('termsCard');
    const scopeCard   = document.getElementById('scopeCard');
    const purposeCard = document.getElementById('purposeCard');
    const slaCard     = document.getElementById('slaCard');
    const amountWrap  = document.getElementById('amountWrap');
    const depositWrap = document.getElementById('depositWrap');
    const balanceWrap = document.getElementById('balanceWrap');

    if (type === 'sla_only') {
        termsCard.style.display   = 'block';
        scopeCard.style.display   = 'none';
        purposeCard.style.display = 'none';
        slaCard.style.display     = 'block';
        amountWrap.style.display  = 'none';
        depositWrap.style.display = 'none';
        balanceWrap.style.display = 'none';
    } else if (type === 'sow_only') {
        termsCard.style.display   = 'none';
        scopeCard.style.display   = 'block';
        purposeCard.style.display = 'none';
        slaCard.style.display     = 'none';
        amountWrap.style.display  = 'block';
        depositWrap.style.display = 'none';
        balanceWrap.style.display = 'none';
    } else {
        // full_contract
        termsCard.style.display   = 'block';
        scopeCard.style.display   = 'block';
        purposeCard.style.display = 'block';
        slaCard.style.display     = 'block';
        amountWrap.style.display  = 'block';
        depositWrap.style.display = 'block';
        balanceWrap.style.display = 'block';
    }
}

// ── Defaults ────────────────────────────────────────────────────────────
const contractorName = @json($settings['site_name'] ?? 'BridgeNovaX Technologies');
let currentClient    = @json($selected ? ($selected->company_name ?? $selected->id) : '');
const effectiveDate  = document.querySelector('input[name="effective_date"]').value;

function buildPurposeText(client, system, contractor) {
    const c = client || '[Client Name]';
    const s = system || 'NovaxPOS';
    const d = formatDate(document.querySelector('input[name="effective_date"]').value) || '[Date]';
    return `This Services Contract Agreement ("Agreement") is entered into as of ${d}, by and between ${contractor} ("Contractor") and ${c} ("Client"). This Agreement governs the development, delivery, and ongoing support of a ${s} management system tailored to the Client's business operations.`;
}

function buildSoftwareDesc(system, contractor) {
    const s = system || 'NovaxPOS';
    return `The Contractor agrees to deliver a comprehensive, web-based Point of Sale and Business Management System — ${s} — featuring inventory management, sales and purchasing, financial reporting, HRM, multi-warehouse support, and role-based access control, as specified in the Scope of Work below.`;
}

function formatDate(val) {
    if (!val) return '';
    const d = new Date(val);
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

function refreshDynamicTexts(forceUpdate) {
    const systemName = document.getElementById('systemName').value.trim() || 'NovaxPOS';
    const purposeTA  = document.getElementById('purposeText');
    const softwareTA = document.getElementById('softwareDesc');

    // Only auto-fill if empty or forceUpdate (user explicitly changed system/tenant)
    if (!purposeTA.value.trim() || forceUpdate) {
        purposeTA.value = buildPurposeText(currentClient, systemName, contractorName);
    }
    if (!softwareTA.value.trim() || forceUpdate) {
        softwareTA.value = buildSoftwareDesc(systemName, contractorName);
    }
}

// ── Tenant select ────────────────────────────────────────────────────────
document.getElementById('tenantSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const preview = document.getElementById('tenantPreview');
    if (opt.value) {
        currentClient = opt.dataset.company || opt.value;
        preview.innerHTML = `<dl class="row mb-0">
            <dt class="col-5 text-muted">Company</dt><dd class="col-7 fw-semibold">${currentClient}</dd>
            <dt class="col-5 text-muted">Email</dt><dd class="col-7" style="word-break:break-all;">${opt.dataset.email || '—'}</dd>
        </dl>`;
    } else {
        currentClient = '';
        preview.innerHTML = '<span class="text-muted">Select a tenant above</span>';
    }
    refreshDynamicTexts(true);
});

// ── System name changes ──────────────────────────────────────────────────
document.getElementById('systemName').addEventListener('input', function() {
    refreshDynamicTexts(true);
});

// ── Effective date changes ───────────────────────────────────────────────
document.querySelector('input[name="effective_date"]').addEventListener('change', function() {
    refreshDynamicTexts(true);
});

// ── Init on page load ────────────────────────────────────────────────────
toggleDocSections();
refreshDynamicTexts(false); // only pre-fill if empty

@if($selected)
currentClient = @json($selected->company_name ?? $selected->id);
document.getElementById('tenantPreview').innerHTML = `<dl class="row mb-0">
    <dt class="col-5 text-muted">Company</dt><dd class="col-7 fw-semibold">{{ $selected->company_name ?? $selected->id }}</dd>
    <dt class="col-5 text-muted">Email</dt><dd class="col-7" style="word-break:break-all;">{{ $selected->email }}</dd>
</dl>`;
refreshDynamicTexts(false);
@endif

// Company logo preview
function previewLogo(input) {
    const wrap    = document.getElementById('logoPreviewWrap');
    const preview = document.getElementById('logoPreview');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('Logo file must be under 2 MB.');
            input.value = '';
            wrap.style.display = 'none';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            wrap.style.display = 'block';
        };
        reader.readAsDataURL(file);
        // Uncheck reuse if user picks a new file
        const rc = document.getElementById('reuseLogoCheck');
        if (rc) rc.checked = false;
    } else {
        wrap.style.display = 'none';
    }
}

// If user checks "reuse", clear the file input so it doesn't override
const reuseLogoCheck = document.getElementById('reuseLogoCheck');
if (reuseLogoCheck) {
    reuseLogoCheck.addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('companyLogoInput').value = '';
            document.getElementById('logoPreviewWrap').style.display = 'none';
        }
    });
}
</script>
@endsection
