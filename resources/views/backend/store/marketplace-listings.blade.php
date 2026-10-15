@extends('backend.layout.main')
@section('title', 'Marketplace Listings')

@section('content')
<section class="forms">
    <div class="container-fluid">
        @include('backend.store.partials.marketplace-hold-banner')


        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem;">
            <div>
                <h5 class="mb-0" style="font-weight:600;">Global Marketplace Listings</h5>
                <small class="text-muted">Choose which products appear on the platform marketplace.</small>
            </div>
            <div class="d-flex" style="gap:.5rem;">
                <form action="{{ route('store.marketplace.publish-all') }}" method="POST"
                      onsubmit="return confirm('Publish ALL your active products to the global marketplace?');">
                    @csrf
                    <button class="btn btn-sm btn-success">
                        <i class="fas fa-cloud-upload-alt" style="margin-right:4px;"></i>Publish All Products
                    </button>
                </form>
                <form action="{{ route('store.marketplace.sync') }}" method="POST">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-sync" style="margin-right:4px;"></i>Sync All Listed
                    </button>
                </form>
                <a href="{{ route('store.platform-fees') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-file-invoice-dollar" style="margin-right:4px;"></i>Platform Fees
                </a>
                <a href="{{ 'https://' . env('CENTRAL_DOMAIN', request()->getHost()) . '/marketplace' }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-external-link-alt" style="margin-right:4px;"></i>View Marketplace
                </a>
            </div>
        </div>

        @if(session('message'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('message') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                {{ session('warning') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:48px"></th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th class="text-center">
                                On Marketplace
                                <div style="font-weight:400;font-size:.7rem;margin-top:2px;white-space:nowrap;">
                                    <label style="cursor:pointer;margin:0;color:#64748b;">
                                        <input type="checkbox" id="listing-select-all" style="vertical-align:middle;"> Select all on page
                                    </label>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        @php $isListed = $listed[$product->id] ?? false; @endphp
                        <tr>
                            <td>
                                @if($product->image)
                                    <img src="{{ asset('images/product/'.$product->image) }}"
                                         style="width:40px;height:40px;object-fit:cover;border-radius:6px;background:#f1f5f9">
                                @else
                                    <div style="width:40px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#cbd5e1">
                                        <i class="fas fa-box" style="font-size:.75rem"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:.875rem">{{ Str::limit($product->name,50) }}</div>
                                <small class="text-muted">{{ $product->code }}</small>
                            </td>
                            <td><small class="text-muted">{{ $product->category?->name ?? '—' }}</small></td>
                            <td class="small" style="font-weight:600;">{{ number_format($product->price,2) }}</td>
                            <td>
                                @if($product->qty > 0)
                                    <span class="badge badge-success">{{ $product->qty }}</span>
                                @else
                                    <span class="badge badge-secondary">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <label class="listing-switch" style="position:relative;display:inline-block;width:42px;height:22px;margin:0;cursor:pointer;">
                                    <input class="listing-toggle" type="checkbox"
                                           data-id="{{ $product->id }}"
                                           {{ $isListed ? 'checked' : '' }}
                                           style="opacity:0;width:0;height:0;">
                                    <span class="listing-slider"
                                          style="position:absolute;inset:0;background:#cbd5e1;border-radius:22px;transition:.2s;"></span>
                                </label>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No active products found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                {{ $products->links() }}
            </div>
        </div>

    </div>
</section>
@endsection

@push('css')
<style>
    .listing-slider::before {
        content: '';
        position: absolute;
        left: 3px; top: 3px;
        width: 16px; height: 16px;
        background: #fff;
        border-radius: 50%;
        transition: .2s;
    }
    .listing-toggle:checked + .listing-slider { background: #28a745; }
    .listing-toggle:checked + .listing-slider::before { transform: translateX(20px); }
    .listing-toggle:disabled + .listing-slider { opacity: .5; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const toggles   = () => Array.from(document.querySelectorAll('.listing-toggle'));
    const master    = document.getElementById('listing-select-all');
    const csrfToken = () => document.querySelector('meta[name=csrf-token]').content;

    // Flip a single product's marketplace state on the server.
    function sendToggle(chk) {
        const id = chk.dataset.id;
        chk.disabled = true;
        return fetch(`/backend/store/marketplace/toggle/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify({})
        })
        .then(r => r.json())
        .then(d => { chk.checked = d.listed; chk.disabled = false; refreshMaster(); })
        .catch(() => {
            chk.checked = !chk.checked; // revert
            chk.disabled = false;
            refreshMaster();
            alert('Failed to update listing. Please try again.');
        });
    }

    // Keep the header checkbox in sync with the rows.
    function refreshMaster() {
        if (!master) return;
        const all = toggles();
        master.checked = all.length > 0 && all.every(c => c.checked);
    }

    // Per-row toggle.
    toggles().forEach(chk => chk.addEventListener('change', () => sendToggle(chk)));

    // Select-all: publish/unlist every product on the current page.
    if (master) {
        master.addEventListener('change', function () {
            const want = this.checked;
            toggles().forEach(chk => {
                if (!chk.disabled && chk.checked !== want) sendToggle(chk);
            });
        });
        refreshMaster();
    }
})();
</script>
@endpush
