@extends('superadmin.layout.main')
@section('title', 'Clients')
@section('page-title', 'Clients')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">All Clients</h5>
    <a href="{{ route('superadmin.clients.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>Add Client
    </a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search by ID, company or email…" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="1" {{ request('status')=='1'?'selected':'' }}>Active</option>
                    <option value="0" {{ request('status')==='0'?'selected':'' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="package_id" class="form-select form-select-sm">
                    <option value="">All Packages</option>
                    @foreach($packages as $pkg)
                        <option value="{{ $pkg->id }}" {{ request('package_id')==$pkg->id?'selected':'' }}>{{ $pkg->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">Filter</button>
                <a href="{{ route('superadmin.clients.index') }}" class="btn btn-sm btn-outline-secondary flex-fill">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID / Domain</th>
                        <th>Company</th>
                        <th>Package</th>
                        <th>Type</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                    <tr>
                        <td>
                            <div class="fw-semibold text-primary">{{ $tenant->id }}</div>
                            <small class="text-muted">{{ $tenant->id }}.{{ env('CENTRAL_DOMAIN', 'novaxpos.com') }}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($tenant->logo_url)
                                    <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->company_name }}"
                                         style="width:32px;height:32px;object-fit:contain;border-radius:4px;border:1px solid #e2e8f0;padding:2px;background:#f8fafc;flex-shrink:0;">
                                @else
                                    <div class="d-flex align-items-center justify-content-center rounded bg-primary text-white fw-bold flex-shrink-0"
                                         style="width:32px;height:32px;font-size:11px;">
                                        {{ $tenant->initials }}
                                    </div>
                                @endif
                                <div>
                                    <div>{{ $tenant->company_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $tenant->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary">{{ $tenant->package->name ?? '—' }}</span></td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $tenant->subscription_type ?? '—')) }}</span></td>
                        <td>
                            @if($tenant->expiry_date)
                                @php $exp = \Carbon\Carbon::parse($tenant->expiry_date); @endphp
                                <span class="{{ $exp->isPast() ? 'text-danger fw-semibold' : ($exp->diffInDays() <= 7 ? 'text-warning fw-semibold' : 'text-muted') }}"
                                      style="font-size:.8rem;">
                                    {{ $exp->format('d M Y') }}
                                    @if(!$exp->isPast())<br><small>({{ $exp->diffForHumans() }})</small>@endif
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($tenant->status)
                                <span class="badge badge-active">Active</span>
                            @else
                                @php
                                    $pendingPayment = \DB::connection('saleprosaas_landlord')
                                        ->table('tenant_payments')
                                        ->where('tenant_id', $tenant->id)
                                        ->whereIn('paid_by', ['bank_transfer', 'mtn_mobile_money', 'orange_money'])
                                        ->where('status', 'pending')
                                        ->latest()
                                        ->first();
                                    $gwLabel = match($pendingPayment->paid_by ?? '') {
                                        'mtn_mobile_money' => 'MTN MoMo',
                                        'orange_money'     => 'Orange Money',
                                        default            => 'Bank Transfer',
                                    };
                                @endphp
                                @if($pendingPayment)
                                    <span class="badge bg-warning text-dark" title="{{ $gwLabel }} — Ref: {{ $pendingPayment->transaction_ref }}">
                                        <i class="fas fa-clock me-1"></i>Pending Payment
                                    </span>
                                @else
                                    <span class="badge badge-inactive">Inactive</span>
                                @endif
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('superadmin.clients.show', $tenant->id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('superadmin.clients.edit', $tenant->id) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('superadmin.clients.impersonate', $tenant->id) }}" class="btn btn-sm btn-outline-warning" title="Impersonate">
                                    <i class="fas fa-user-secret"></i>
                                </a>
                                <form action="{{ route('superadmin.clients.toggle-status', $tenant->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm {{ $tenant->status ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                            title="{{ $tenant->status ? 'Suspend' : 'Activate' }}">
                                        <i class="fas {{ $tenant->status ? 'fa-ban' : 'fa-check' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('superadmin.clients.destroy', $tenant->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this client and all their data?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No clients found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($tenants->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing {{ $tenants->firstItem() }}–{{ $tenants->lastItem() }} of {{ $tenants->total() }}</small>
        {{ $tenants->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
