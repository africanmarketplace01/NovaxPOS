@extends('superadmin.layout.main')
@section('title', 'Marketplace Listings')
@section('page-title', 'Product Listings')

@section('content')

@if(session('message'))
<div class="alert alert-success">{{ session('message') }}</div>
@endif

<form method="GET" action="{{ route('superadmin.marketplace.listings') }}" class="row g-2 mb-3 align-items-end">
    <div class="col-md-3">
        <select name="tenant" class="form-select form-select-sm">
            <option value="">All shops</option>
            @foreach($tenants as $t)
            <option value="{{ $t->tenant_id }}" {{ request('tenant')===$t->tenant_id ? 'selected' : '' }}>
                {{ $t->tenant_store_name ?: $t->tenant_id }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="approved" class="form-select form-select-sm">
            <option value="">All visibility</option>
            <option value="1" {{ request('approved')==='1' ? 'selected' : '' }}>Visible</option>
            <option value="0" {{ request('approved')==='0' ? 'selected' : '' }}>Hidden</option>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-primary">Filter</button>
        <a href="{{ route('superadmin.marketplace.listings') }}" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Product</th><th>Shop</th><th>Category</th><th>Price</th><th>Stock</th><th>Visible</th><th>Synced</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($listings as $l)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($l->image_url)
                                <img src="{{ $l->image_url }}" style="width:36px;height:36px;object-fit:cover;border-radius:6px;background:#f1f5f9">
                            @else
                                <div style="width:36px;height:36px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#cbd5e1"><i class="fas fa-box" style="font-size:.75rem"></i></div>
                            @endif
                            <div>
                                <div class="fw-semibold small">{{ Str::limit($l->name,40) }}</div>
                            </div>
                        </div>
                    </td>
                    <td><small>{{ $l->tenant_store_name ?: $l->tenant_id }}</small></td>
                    <td><small class="text-muted">{{ $l->category ?: '—' }}</small></td>
                    <td class="small fw-semibold">{{ $l->currency }} {{ number_format($l->price,2) }}</td>
                    <td>
                        @if($l->in_stock)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $l->stock_qty }}</span>
                        @else
                            <span class="badge bg-secondary">Out</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('superadmin.marketplace.listings.toggle', $l->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $l->approved ? 'btn-success' : 'btn-outline-secondary' }}" title="{{ $l->approved ? 'Hide listing' : 'Show listing' }}">
                                <i class="fas {{ $l->approved ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                            </button>
                        </form>
                    </td>
                    <td class="text-muted small">{{ $l->synced_at?->diffForHumans() ?? 'Never' }}</td>
                    <td>
                        <form action="{{ route('superadmin.marketplace.listings.delete', $l->id) }}" method="POST" onsubmit="return confirm('Remove this listing?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No listings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $listings->withQueryString()->links() }}</div>
</div>
@endsection
