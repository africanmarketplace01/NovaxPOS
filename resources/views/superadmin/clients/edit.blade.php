@extends('superadmin.layout.main')
@section('title', 'Edit: ' . ($tenant->company_name ?? $tenant->id))
@section('page-title', 'Edit Client')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('superadmin.clients.show', $tenant->id) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3">
                <i class="fas fa-edit me-2"></i>Edit — {{ $tenant->company_name ?? $tenant->id }}
            </div>
            <div class="card-body">
                <form action="{{ route('superadmin.clients.update', $tenant->id) }}" method="POST">
                    @csrf @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tenant ID</label>
                            <input type="text" class="form-control" value="{{ $tenant->id }}" readonly disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control"
                                   value="{{ old('company_name', $tenant->company_name) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="{{ old('email', $tenant->email) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone_number" class="form-control"
                                   value="{{ old('phone_number', $tenant->phone_number) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Package</label>
                            <select name="package_id" class="form-select">
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}" {{ old('package_id', $tenant->package_id)==$pkg->id?'selected':'' }}>
                                        {{ $pkg->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" {{ old('status', $tenant->status)==1?'selected':'' }}>Active</option>
                                <option value="0" {{ old('status', $tenant->status)==0?'selected':'' }}>Inactive</option>
                            </select>
                        </div>
                    </div>

                    {{-- White-label Custom Domain --}}
                    <hr class="my-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-globe me-1" style="color:var(--lp-primary)"></i>
                            Custom Domain (White-label)
                        </label>
                        @php
                            $tenantData = is_array($tenant->data) ? $tenant->data : json_decode($tenant->data ?? '{}', true);
                            $customDomain = $tenantData['custom_domain'] ?? '';
                        @endphp
                        <input type="text" name="custom_domain" class="form-control"
                               value="{{ old('custom_domain', $customDomain) }}"
                               placeholder="e.g. pos.acmesuperstore.com">
                        <div class="form-text">
                            Leave blank to use the default subdomain
                            <code>{{ $tenant->id }}.{{ env('CENTRAL_DOMAIN') }}</code>.
                            If set, point the domain's DNS <strong>A record</strong> or <strong>CNAME</strong>
                            to this server's IP before activating.
                        </div>
                    </div>

                    {{-- Plan permission controls --}}
                    <hr class="my-4">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="force_reapply_permissions"
                                   id="force_reapply_permissions" value="1">
                            <label class="form-check-label" for="force_reapply_permissions">
                                <strong>Re-apply plan permissions</strong>
                                <span class="text-muted fw-normal d-block" style="font-size:.8rem;">
                                    Forces the sidebar &amp; feature access to sync with the selected plan,
                                    even if the plan hasn't changed. Use this after editing a plan's feature list.
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('superadmin.clients.show', $tenant->id) }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
