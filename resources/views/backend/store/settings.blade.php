@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">

                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="mb-0"><i class="fas fa-store me-2" style="color:var(--lp-primary,#0066CC)"></i>E-commerce Store</h4>
                        <small class="text-muted">Manage your public online storefront</small>
                    </div>
                    <div class="d-flex gap-2">
                        @if($store->enabled)
                        <a href="{{ route('store.index') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt me-1"></i> View Store
                        </a>
                        @endif
                        <a href="{{ route('store.qr') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-qrcode me-1"></i> QR Code
                        </a>
                        <a href="{{ route('store.orders') }}" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-list-alt me-1"></i> Orders
                        </a>
                    </div>
                </div>

                {{-- Store URL banner --}}
                <div style="background:linear-gradient(135deg,#003366,#0066CC);border-radius:12px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;color:#fff;display:flex;align-items:center;gap:1rem">
                    <i class="fas fa-link fa-2x" style="opacity:.7"></i>
                    <div>
                        <div style="font-size:.75rem;opacity:.7;text-transform:uppercase;letter-spacing:.06em">Your Store URL</div>
                        <div style="font-size:1rem;font-weight:700">{{ $storeUrl }}</div>
                    </div>
                    <button onclick="navigator.clipboard.writeText('{{ $storeUrl }}')" class="btn btn-sm ms-auto"
                            style="background:rgba(255,255,255,.2);color:#fff;border:none;border-radius:6px">
                        <i class="fas fa-copy me-1"></i> Copy
                    </button>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header"><i class="fas fa-cog me-2"></i>Store Settings</div>
                            <div class="card-body">
                                <form action="{{ route('store.settings.update') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    {{-- Enable toggle --}}
                                    <div class="mb-4 p-3 rounded" style="background:#f8fafc;border:1px solid #e2e8f0">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <div class="fw-600">Store Status</div>
                                                <small class="text-muted">When enabled, customers can browse and order from your store</small>
                                            </div>
                                            <div class="form-check form-switch ms-3">
                                                <input class="form-check-input" type="checkbox" name="enabled" id="store_enabled" role="switch"
                                                       value="1" style="width:3em;height:1.6em;cursor:pointer"
                                                       {{ $store->enabled ? 'checked' : '' }}>
                                                <label class="form-check-label fw-600 ms-1" for="store_enabled" id="store-status-label">
                                                    {{ $store->enabled ? 'Live' : 'Offline' }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-600">Store Name *</label>
                                            <input type="text" class="form-control" name="store_name"
                                                   value="{{ old('store_name', $store->store_name) }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-600">Currency</label>
                                            <input type="text" class="form-control" name="store_currency"
                                                   value="{{ old('store_currency', $store->store_currency ?? config('currency', 'USD')) }}"
                                                   placeholder="USD">
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label fw-600">About the Store</label>
                                        <textarea name="store_about" class="form-control" rows="3"
                                                  placeholder="Short description of your store shown to customers…">{{ old('store_about', $store->store_about) }}</textarea>
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label fw-600">Store Banner</label>
                                        <input type="file" class="form-control" name="store_banner" accept="image/*">
                                        @if($store->store_banner)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $store->store_banner) }}" alt="Banner"
                                                 style="max-height:80px;border-radius:8px;border:1px solid #e2e8f0">
                                        </div>
                                        @endif
                                        <div class="form-text">Displayed at the top of your store homepage. Recommended: 1200×400px.</div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label fw-600">WhatsApp Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text" style="background:#25D366;color:#fff;border-color:#25D366">
                                                <i class="fab fa-whatsapp"></i>
                                            </span>
                                            <input type="text" class="form-control" name="whatsapp_number"
                                                   value="{{ old('whatsapp_number', $store->whatsapp_number) }}"
                                                   placeholder="+1234567890">
                                        </div>
                                        <div class="form-text">With country code. Customers can click to chat directly from your store.</div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label fw-600">Checkout Note</label>
                                        <input type="text" class="form-control" name="order_note"
                                               value="{{ old('order_note', $store->order_note) }}"
                                               placeholder="e.g. Delivery available within 5km radius">
                                        <div class="form-text">Shown on the checkout page.</div>
                                    </div>

                                    <hr class="my-4">

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="show_out_of_stock"
                                                       id="show_oos" value="1" {{ $store->show_out_of_stock ? 'checked' : '' }}>
                                                <label class="form-check-label" for="show_oos">
                                                    Show out-of-stock products
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="allow_orders"
                                                       id="allow_orders" value="1" {{ $store->allow_orders ? 'checked' : '' }}>
                                                <label class="form-check-label" for="allow_orders">
                                                    Allow online orders (checkout enabled)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    {{-- Tax --}}
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-600">Tax Rate (%)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="tax_rate"
                                                       step="0.01" min="0" max="100"
                                                       value="{{ old('tax_rate', $store->tax_rate ?? 0) }}">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Applied to all store orders</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-600">Store Email</label>
                                            <input type="email" class="form-control" name="store_email"
                                                   value="{{ old('store_email', $store->store_email) }}"
                                                   placeholder="contact@yourstore.com">
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    {{-- Payment methods --}}
                                    <div class="fw-600 mb-2"><i class="fas fa-credit-card me-2"></i>Accepted Payment Methods</div>
                                    <div class="row g-2">
                                        @php
                                        $pmOpts = [
                                            'accept_cod'           => ['Cash on Delivery','fa-money-bill-wave','#22c55e'],
                                            'accept_stripe'        => ['Card (Stripe)','fa-credit-card','#635bff'],
                                            'accept_paystack'      => ['Paystack','fa-credit-card','#00c3f7'],
                                            'accept_bank_transfer' => ['Bank Transfer','fa-university','#0ea5e9'],
                                            'accept_mtn'           => ['MTN Mobile Money','fa-mobile-alt','#fbbf24'],
                                            'accept_orange'        => ['Orange Money','fa-mobile-alt','#f97316'],
                                        ];
                                        @endphp
                                        @foreach($pmOpts as $field => [$label, $icon, $color])
                                        <div class="col-md-4">
                                            <div class="form-check p-3 rounded" style="border:1px solid #e2e8f0">
                                                <input class="form-check-input" type="checkbox"
                                                       name="{{ $field }}" id="{{ $field }}" value="1"
                                                       {{ $store->$field ?? ($field==='accept_cod') ? 'checked' : '' }}>
                                                <label class="form-check-label d-flex align-items-center gap-2" for="{{ $field }}">
                                                    <i class="fas {{ $icon }}" style="color:{{ $color }}"></i>
                                                    <span style="font-size:.875rem">{{ $label }}</span>
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>

                                    <hr class="my-4">

                                    {{-- Global marketplace listing --}}
                                    <div class="p-3 rounded" style="background:#f0f9ff;border:1px solid #bae6fd">
                                        <div class="d-flex align-items-start gap-3">
                                            <i class="fas fa-globe mt-1" style="color:#0ea5e9;font-size:1.25rem"></i>
                                            <div class="flex-fill">
                                                <div class="fw-600">List on Global Marketplace</div>
                                                <div class="text-muted small mb-2">When enabled, you can choose specific products to appear on the platform-wide marketplace, reaching customers beyond your store page.</div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="marketplace_listed" id="mkt_listed"
                                                           value="1" {{ $store->marketplace_listed ? 'checked' : '' }}
                                                           style="width:2.5rem;height:1.25rem;cursor:pointer">
                                                    <label class="form-check-label" for="mkt_listed">
                                                        {{ $store->marketplace_listed ? 'Enabled — products can be listed' : 'Disabled' }}
                                                    </label>
                                                </div>
                                                @if($store->marketplace_listed)
                                                <a href="{{ route('store.marketplace.listings') }}" class="btn btn-sm btn-outline-primary mt-2">
                                                    <i class="fas fa-list me-1"></i>Manage Listings
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-save me-2"></i>Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Sidebar: QR + stats --}}
                    <div class="col-lg-4">
                        {{-- QR preview card --}}
                        <div class="card mb-3">
                            <div class="card-header"><i class="fas fa-qrcode me-2"></i>QR Code</div>
                            <div class="card-body text-center">
                                <canvas id="mini-qr" style="border-radius:8px;border:1px dashed #e2e8f0;padding:8px"></canvas>
                                <div style="font-size:.72rem;color:#64748b;margin-top:.5rem;word-break:break-all;font-family:monospace">{{ $storeUrl }}</div>
                                <div class="d-flex gap-2 mt-3 justify-content-center">
                                    <a href="{{ route('store.qr') }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-print me-1"></i> Print QR
                                    </a>
                                    <button onclick="downloadMiniQR()" class="btn btn-sm btn-light">
                                        <i class="fas fa-download me-1"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Recent orders summary --}}
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <span><i class="fas fa-shopping-bag me-2"></i>Recent Orders</span>
                                <a href="{{ route('store.orders') }}" class="btn btn-xs btn-light" style="font-size:.75rem;padding:.2rem .6rem;border-radius:6px">View All</a>
                            </div>
                            <div class="card-body p-0">
                                @if($orders->count())
                                <table class="table table-sm table-hover mb-0" style="font-size:.82rem">
                                    <tbody>
                                        @foreach($orders->take(5) as $order)
                                        <tr>
                                            <td style="padding:.6rem 1rem">
                                                <div class="fw-600" style="font-size:.8rem;font-family:monospace">{{ $order->order_number }}</div>
                                                <div style="font-size:.72rem;color:#94a3b8">{{ $order->customer_name }}</div>
                                            </td>
                                            <td style="padding:.6rem;text-align:right;vertical-align:middle">
                                                <div class="fw-600">{{ number_format($order->total, 2) }}</div>
                                                <span style="font-size:.65rem;padding:.1em .45em;border-radius:999px;font-weight:700;
                                                    background:{{ $order->status === 'completed' ? '#dcfce7' : ($order->status === 'cancelled' ? '#fee2e2' : '#fef3c7') }};
                                                    color:{{ $order->status === 'completed' ? '#166534' : ($order->status === 'cancelled' ? '#991b1b' : '#92400e') }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @else
                                <div style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:.85rem">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No orders yet
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
    // Generate mini QR on sidebar
    QRCode.toCanvas(document.getElementById('mini-qr'), '{{ $storeUrl }}', {
        width: 160, margin: 1, color: { dark: '#003366', light: '#ffffff' }
    });

    function downloadMiniQR() {
        var link = document.createElement('a');
        link.download = 'store-qr.png';
        link.href = document.getElementById('mini-qr').toDataURL('image/png');
        link.click();
    }

    // Toggle label
    document.getElementById('store_enabled').addEventListener('change', function() {
        document.getElementById('store-status-label').textContent = this.checked ? 'Live' : 'Offline';
    });
</script>
@endpush
