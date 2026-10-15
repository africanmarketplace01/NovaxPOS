@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:.5rem">
            <div>
                <h4 class="mb-0"><i class="fas fa-truck me-2" style="color:var(--lp-primary,#0066CC)"></i>Delivery</h4>
                <small class="text-muted">Your delivery agencies, zones and zone-to-zone pricing</small>
            </div>
            <a href="{{ route('store.settings') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Store Settings
            </a>
        </div>

        @if(session('message') || session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('message') ?? session('success') }}
                <button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert">&times;</button></div>
        @endif
        @if(session('not_permitted') || session('error'))
            <div class="alert alert-danger">{{ session('not_permitted') ?? session('error') }}</div>
        @endif

        {{-- Add agency --}}
        <div class="card mb-4">
            <div class="card-header"><strong>Add a delivery agency</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('store.delivery.agencies.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><input name="name" class="form-control" placeholder="Agency name *" required></div>
                    <div class="col-md-3"><input name="phone" class="form-control" placeholder="Phone"></div>
                    <div class="col-md-3"><input name="base_rate" type="number" step="0.01" min="0" class="form-control" placeholder="Base rate"></div>
                    <div class="col-md-2 d-grid"><button class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add</button></div>
                </form>
            </div>
        </div>

        @forelse($agencies as $agency)
            @php $zones = $zonesByAgency[$agency->id] ?? collect(); $prices = $pricesByAgency[$agency->id] ?? collect(); @endphp
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <strong>{{ $agency->name }}</strong>
                        <span class="text-muted ms-2" style="font-size:.85rem">base {{ $agency->currency }} {{ number_format($agency->base_rate,2) }}</span>
                    </div>
                    <form method="POST" action="{{ route('store.delivery.agencies.delete', $agency->id) }}" onsubmit="return confirm('Deactivate this agency?')">
                        @csrf<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="row">
                        {{-- Zones --}}
                        <div class="col-md-5">
                            <h6 class="mb-2">Zones</h6>
                            @if($zones->isEmpty())
                                <p class="text-muted" style="font-size:.85rem">No zones yet. Add zones like "Sinkor", "Paynesville".</p>
                            @else
                                <div class="d-flex flex-wrap mb-2" style="gap:.4rem">
                                    @foreach($zones as $z)
                                        <span class="badge bg-light text-dark border d-inline-flex align-items-center" style="gap:.35rem">
                                            {{ $z->name }}
                                            <form method="POST" action="{{ route('store.delivery.zones.delete', $z->id) }}" class="d-inline" onsubmit="return confirm('Remove zone?')">
                                                @csrf<button class="btn btn-link p-0 text-danger" style="line-height:1">&times;</button>
                                            </form>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            <form method="POST" action="{{ route('store.delivery.zones.store', $agency->id) }}" class="input-group input-group-sm mt-1" style="max-width:320px">
                                @csrf
                                <input name="name" class="form-control" placeholder="New zone name" required>
                                <button class="btn btn-outline-primary">Add zone</button>
                            </form>
                        </div>

                        {{-- Pricing --}}
                        <div class="col-md-7">
                            <h6 class="mb-2">Zone-to-zone pricing</h6>
                            @if($prices->isNotEmpty())
                                <table class="table table-sm" style="font-size:.85rem">
                                    <thead><tr><th>From</th><th>To</th><th class="text-end">Price</th></tr></thead>
                                    <tbody>
                                        @foreach($prices as $p)
                                            <tr>
                                                <td>{{ optional($zones->firstWhere('id',$p->from_zone_id))->name ?? '—' }}</td>
                                                <td>{{ optional($zones->firstWhere('id',$p->to_zone_id))->name ?? '—' }}</td>
                                                <td class="text-end">{{ $agency->currency }} {{ number_format($p->price,2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted" style="font-size:.85rem">No pricing set. Prices apply when a customer picks their from/to zones.</p>
                            @endif

                            @if($zones->count() >= 1)
                                <form method="POST" action="{{ route('store.delivery.prices.set', $agency->id) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col"><label class="form-label mb-0" style="font-size:.75rem">From</label>
                                        <select name="from_zone_id" class="form-select form-select-sm" required>
                                            @foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->name }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col"><label class="form-label mb-0" style="font-size:.75rem">To</label>
                                        <select name="to_zone_id" class="form-select form-select-sm" required>
                                            @foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->name }}</option>@endforeach
                                        </select>
                                    </div>
                                    <div class="col"><label class="form-label mb-0" style="font-size:.75rem">Price</label>
                                        <input name="price" type="number" step="0.01" min="0" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-auto"><button class="btn btn-sm btn-primary">Save</button></div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5">
                <i class="fas fa-truck fa-2x mb-2 d-block"></i>
                Add your own delivery agency above to set zones and zone-to-zone pricing.
            </div></div>
        @endforelse
    </div>
</section>
@endsection
