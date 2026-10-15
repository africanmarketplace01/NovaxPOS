@extends('superadmin.layout.main')
@section('title', 'Add Client')
@section('page-title', 'Add New Client')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('superadmin.clients.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3"><i class="fas fa-user-plus me-2"></i>Client Information</div>
            <div class="card-body">
                <form action="{{ route('superadmin.clients.store') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Subdomain / Tenant ID <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="tenant" class="form-control @error('tenant') is-invalid @enderror"
                                       value="{{ old('tenant') }}" placeholder="e.g. acmeretail" required>
                                <span class="input-group-text">.{{ env('CENTRAL_DOMAIN', 'novaxpos.com') }}</span>
                            </div>
                            <div class="form-text">Lowercase letters and numbers only, 3–30 chars.</div>
                            @error('tenant')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Admin Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Admin Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Admin Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control"
                                   value="{{ old('company_name') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone_number" class="form-control"
                                   value="{{ old('phone_number') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Package <span class="text-danger">*</span></label>
                            <select name="package_id" class="form-select @error('package_id') is-invalid @enderror" required>
                                <option value="">Select Package</option>
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}" {{ old('package_id')==$pkg->id?'selected':'' }}>
                                        {{ $pkg->name }} — ${{ number_format($pkg->price_monthly, 2) }}/mo
                                    </option>
                                @endforeach
                            </select>
                            @error('package_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Subscription Type <span class="text-danger">*</span></label>
                            <select name="subscription_type" class="form-select" required>
                                <option value="free_trial" {{ old('subscription_type')=='free_trial'?'selected':'' }}>Free Trial</option>
                                <option value="monthly"    {{ old('subscription_type')=='monthly'?'selected':'' }}>Monthly</option>
                                <option value="yearly"     {{ old('subscription_type')=='yearly'?'selected':'' }}>Yearly</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('superadmin.clients.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i>Create Client
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
