@extends('superadmin.layout.main')
@section('title', 'Packages')
@section('page-title', 'Subscription Packages')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Packages</h5>
    <a href="{{ route('superadmin.packages.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i>New Package
    </a>
</div>

<div class="row g-3">
    @forelse($packages as $pkg)
    <div class="col-lg-4 col-md-6">
        <div class="card h-100 {{ !$pkg->is_active ? 'opacity-50' : '' }}">
            <div class="card-header py-3 d-flex justify-content-between align-items-start">
                <div>
                    <span class="fw-semibold">{{ $pkg->name }}</span>
                    @if($pkg->badge)
                        <span class="badge bg-warning text-dark ms-2">{{ $pkg->badge }}</span>
                    @endif
                    <div class="mt-1">
                        @if($pkg->show_on_public ?? true)
                            <span class="badge bg-info text-dark" title="Visible on landing &amp; pricing pages">
                                <i class="fas fa-globe me-1"></i>Public
                            </span>
                        @else
                            <span class="badge bg-secondary" title="Hidden from public pages — admin use only">
                                <i class="fas fa-lock me-1"></i>Private
                            </span>
                        @endif
                    </div>
                </div>
                <span class="badge {{ $pkg->is_active ? 'bg-success' : 'bg-secondary' }}">
                    {{ $pkg->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="card-body">
                @if($pkg->description)
                    <p class="text-muted small">{{ $pkg->description }}</p>
                @endif

                <div class="d-flex gap-3 mb-3 flex-wrap">
                    @if($pkg->price_monthly > 0)
                    <div>
                        <div class="fs-4 fw-bold text-primary">${{ number_format($pkg->price_monthly, 2) }}</div>
                        <small class="text-muted">/month</small>
                    </div>
                    @endif
                    @if($pkg->price_yearly > 0)
                    <div>
                        <div class="fs-4 fw-bold text-success">${{ number_format($pkg->price_yearly, 2) }}</div>
                        <small class="text-muted">/year</small>
                        @if($pkg->getYearlySavingPercent() > 0)
                            <span class="badge bg-success ms-1">Save {{ $pkg->getYearlySavingPercent() }}%</span>
                        @endif
                    </div>
                    @endif
                    @if($pkg->price_onetime > 0)
                    <div>
                        <div class="fs-4 fw-bold text-warning">${{ number_format($pkg->price_onetime, 2) }}</div>
                        <small class="text-muted">lifetime</small>
                        <span class="badge bg-warning text-dark ms-1">One-off</span>
                    </div>
                    @endif
                </div>

                <ul class="list-unstyled mb-3" style="font-size:.85rem;">
                    <li><i class="fas fa-box-open text-muted me-2"></i>
                        {{ $pkg->product_limit ? number_format($pkg->product_limit).' products' : 'Unlimited products' }}
                    </li>
                    <li><i class="fas fa-users text-muted me-2"></i>
                        {{ $pkg->user_limit ? number_format($pkg->user_limit).' users' : 'Unlimited users' }}
                    </li>
                    <li><i class="fas fa-warehouse text-muted me-2"></i>
                        {{ $pkg->warehouse_limit ? number_format($pkg->warehouse_limit).' warehouses' : 'Unlimited warehouses' }}
                    </li>
                    @if($pkg->is_free_trial)
                        <li><i class="fas fa-hourglass-half text-warning me-2"></i>
                            {{ $pkg->trial_days }} day free trial
                        </li>
                    @endif
                </ul>

                @if(!empty($pkg->features))
                <div class="mb-3">
                    @foreach($pkg->features as $feature)
                        <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:.7rem;">
                            {{ str_replace('_', ' ', $feature) }}
                        </span>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="{{ route('superadmin.packages.edit', $pkg->id) }}" class="btn btn-sm btn-outline-primary flex-fill">
                    <i class="fas fa-edit me-1"></i>Edit
                </a>
                <form action="{{ route('superadmin.packages.destroy', $pkg->id) }}" method="POST" class="flex-fill"
                      onsubmit="return confirm('Delete this package?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger w-100">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                No packages found. <a href="{{ route('superadmin.packages.create') }}">Create one</a>.
            </div>
        </div>
    </div>
    @endforelse
</div>
@endsection
