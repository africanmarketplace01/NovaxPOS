@extends('backend.layout.main')
@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:.5rem">
            <div>
                <h4 class="mb-0"><i class="fas fa-shopping-bag me-2" style="color:var(--lp-primary,#0066CC)"></i>Store Orders</h4>
                <small class="text-muted">Orders placed through your online store &amp; the global marketplace</small>
            </div>
            <a href="{{ route('store.settings') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Store Settings
            </a>
        </div>

        @if(session('success') || session('message'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') ?? session('message') }}
            <button type="button" class="close" data-dismiss="alert" data-bs-dismiss="alert">&times;</button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table order-table mb-0" style="font-size:.875rem">
                        <thead class="thead-light">
                            <tr>
                                <th style="padding:.75rem 1rem">Order #</th>
                                <th>Customer</th>
                                <th class="text-center">Items</th>
                                <th class="text-end">Total</th>
                                <th>Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-end" style="padding-right:1rem">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            @php
                                $st = $order->status;
                                $badgeBg = $st === 'completed' ? '#dcfce7' : ($st === 'delivered' ? '#d1fae5' : ($st === 'cancelled' ? '#fee2e2' : ($st === 'processing' ? '#dbeafe' : ($st === 'shipped' ? '#ede9fe' : '#fef3c7'))));
                                $badgeFg = $st === 'completed' ? '#166534' : ($st === 'delivered' ? '#047857' : ($st === 'cancelled' ? '#991b1b' : ($st === 'processing' ? '#1d4ed8' : ($st === 'shipped' ? '#6d28d9' : '#92400e'))));
                                $isMkt   = ($order->source ?? null) === 'marketplace';
                                $cur     = $order->currency ?: '';
                            @endphp
                            <tr class="order-row">
                                <td style="padding:.75rem 1rem">
                                    <span style="font-family:monospace;font-weight:700;font-size:.8rem;color:var(--lp-primary,#0066CC)">{{ $order->order_number }}</span>
                                    <div style="margin-top:3px">
                                        <span class="src-tag" style="background:{{ $isMkt ? '#eef2ff' : '#f1f5f9' }};color:{{ $isMkt ? '#4f46e5' : '#475569' }}">
                                            <i class="fas {{ $isMkt ? 'fa-globe' : 'fa-store' }}" style="font-size:.6rem"></i> {{ $isMkt ? 'Marketplace' : 'Online store' }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-600">{{ $order->customer_name }}</div>
                                    <div style="font-size:.75rem;color:#94a3b8">{{ $order->customer_phone }}</div>
                                </td>
                                <td class="text-center">
                                    @if(is_array($order->items))
                                    <span class="badge badge-light" style="background:#f1f5f9;color:#475569">{{ count($order->items) }} item{{ count($order->items) !== 1 ? 's' : '' }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-700">{{ $cur }} {{ number_format($order->total, 2) }}</td>
                                <td style="font-size:.8rem;color:#64748b">{{ $order->created_at?->format('d M Y H:i') }}</td>
                                <td class="text-center">
                                    <span class="status-badge" style="background:{{ $badgeBg }};color:{{ $badgeFg }}">{{ ucfirst($st) }}</span>
                                </td>
                                <td class="text-end" style="padding-right:1rem">
                                    <button class="btn btn-sm order-view-btn" data-id="{{ $order->id }}"
                                            style="font-size:.75rem;padding:.3rem .7rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#334155">
                                        <i class="fas fa-eye me-1"></i>View
                                    </button>
                                </td>
                            </tr>

                            {{-- Expandable detail row (hidden until "View") --}}
                            <tr class="order-detail" id="detail-{{ $order->id }}" style="display:none">
                                <td colspan="7" style="background:#f8fafc;padding:0">
                                    <div class="detail-panel">
                                        <div class="detail-grid">
                                            <div class="detail-card">
                                                <div class="detail-label"><i class="fas fa-user me-1"></i>Customer</div>
                                                <div class="detail-strong">{{ $order->customer_name }}</div>
                                                @if($order->customer_email)<div class="detail-sub">{{ $order->customer_email }}</div>@endif
                                                @if($order->customer_phone)<div class="detail-sub">{{ $order->customer_phone }}</div>@endif
                                            </div>
                                            <div class="detail-card">
                                                <div class="detail-label"><i class="fas fa-map-marker-alt me-1"></i>Delivery address</div>
                                                <div class="detail-sub">{{ $order->customer_address ?: 'No address provided' }}</div>
                                                @if($order->note)<div class="detail-sub" style="margin-top:4px"><em>Note: {{ $order->note }}</em></div>@endif
                                            </div>
                                            <div class="detail-card">
                                                <div class="detail-label"><i class="fas fa-truck me-1"></i>Delivery agency</div>
                                                @if($order->delivery_agency_name)
                                                    <div class="detail-strong">{{ $order->delivery_agency_name }}</div>
                                                    <div class="detail-sub">Fee: {{ $cur }} {{ number_format($order->delivery_fee ?? 0, 2) }}</div>
                                                    @if($order->estimated_delivery)<div class="detail-sub">Est. delivery: {{ $order->estimated_delivery }}</div>@endif
                                                @else
                                                    <div class="detail-sub" style="display:flex;align-items:center;gap:6px;color:#94a3b8">
                                                        <i class="fas fa-store"></i> Self / store pickup — no delivery agency assigned
                                                    </div>
                                                    @if(($order->delivery_fee ?? 0) > 0)<div class="detail-sub">Delivery fee: {{ $cur }} {{ number_format($order->delivery_fee, 2) }}</div>@endif
                                                @endif
                                            </div>
                                            <div class="detail-card">
                                                <div class="detail-label"><i class="fas fa-credit-card me-1"></i>Payment</div>
                                                <div class="detail-sub">Method: {{ $order->payment_method ? ucfirst(str_replace('_',' ',$order->payment_method)) : '—' }}</div>
                                                <div class="detail-sub">Status: {{ ucfirst($order->payment_status ?? 'pending') }}</div>
                                                @if($order->marketplace_order_number)<div class="detail-sub">Ref: {{ $order->marketplace_order_number }}</div>@endif
                                            </div>
                                        </div>

                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm items-table mb-0">
                                                <thead>
                                                    <tr><th>Product</th><th>Code</th><th class="text-end">Price</th><th class="text-center">Qty</th><th class="text-end">Subtotal</th></tr>
                                                </thead>
                                                <tbody>
                                                    @if(is_array($order->items))
                                                    @foreach($order->items as $item)
                                                    <tr>
                                                        <td>{{ $item['name'] ?? 'Item' }}</td>
                                                        <td style="font-family:monospace;font-size:.8rem;color:#64748b">{{ $item['code'] ?? '—' }}</td>
                                                        <td class="text-end">{{ $cur }} {{ number_format($item['price'] ?? 0, 2) }}</td>
                                                        <td class="text-center">{{ $item['qty'] ?? 1 }}</td>
                                                        <td class="text-end fw-600">{{ $cur }} {{ number_format($item['subtotal'] ?? (($item['price'] ?? 0) * ($item['qty'] ?? 1)), 2) }}</td>
                                                    </tr>
                                                    @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="detail-footer">
                                            <div class="totals">
                                                <div><span>Subtotal</span><span>{{ $cur }} {{ number_format($order->subtotal ?? 0, 2) }}</span></div>
                                                @if(($order->delivery_fee ?? 0) > 0)<div><span>Delivery</span><span>{{ $cur }} {{ number_format($order->delivery_fee, 2) }}</span></div>@endif
                                                @if(($order->platform_fee ?? 0) > 0)<div><span>Platform fee</span><span>{{ $cur }} {{ number_format($order->platform_fee, 2) }}</span></div>@endif
                                                @if(($order->tax_amount ?? 0) > 0)<div><span>Tax</span><span>{{ $cur }} {{ number_format($order->tax_amount, 2) }}</span></div>@endif
                                                <div class="grand"><span>Total</span><span>{{ $cur }} {{ number_format($order->total, 2) }}</span></div>
                                            </div>

                                            <form action="{{ route('store.orders.status', $order->id) }}" method="POST" class="status-form">
                                                @csrf
                                                <label style="font-size:.75rem;color:#64748b;margin:0">Update status</label>
                                                <select name="status" class="form-control form-control-sm" style="width:auto;min-width:130px">
                                                    @foreach(['pending','processing','shipped','delivered','completed','cancelled'] as $opt)
                                                    <option value="{{ $opt }}" {{ $order->status === $opt ? 'selected' : '' }}>{{ ucfirst($opt) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center;padding:3rem;color:#94a3b8">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>No orders yet. Share your store URL to start receiving orders!
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                <div class="p-3">{{ $orders->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('css')
<style>
    .order-table th { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#64748b; border-top:none; }
    .order-table td { vertical-align:middle; }
    .order-row:hover { background:#f8fafc; }
    .status-badge { font-size:.72rem; padding:.35em .8em; border-radius:999px; font-weight:600; display:inline-block; }
    .src-tag { font-size:.65rem; padding:.15em .55em; border-radius:6px; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
    .order-view-btn.active { background:var(--lp-primary,#0066CC)!important; color:#fff!important; border-color:var(--lp-primary,#0066CC)!important; }

    .detail-panel { padding:1rem 1.25rem 1.25rem; border-top:2px solid var(--lp-primary,#0066CC); }
    .detail-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:.75rem; }
    .detail-card { background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:.7rem .85rem; }
    .detail-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:#94a3b8; font-weight:700; margin-bottom:.35rem; }
    .detail-strong { font-weight:600; font-size:.9rem; color:#1e293b; }
    .detail-sub { font-size:.8rem; color:#64748b; line-height:1.5; }
    .items-table { background:#fff; border:1px solid #e5e9f0; border-radius:10px; overflow:hidden; }
    .items-table thead th { background:#f1f5f9; font-size:.7rem; text-transform:uppercase; letter-spacing:.03em; color:#64748b; border:none; }
    .items-table td { font-size:.83rem; }

    .detail-footer { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem; margin-top:1rem; }
    .totals { min-width:220px; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:.6rem .85rem; }
    .totals > div { display:flex; justify-content:space-between; font-size:.83rem; color:#64748b; padding:.15rem 0; }
    .totals .grand { border-top:1px solid #e5e9f0; margin-top:.3rem; padding-top:.4rem; font-weight:700; color:#1e293b; font-size:.95rem; }
    .status-form { display:flex; align-items:center; gap:.5rem; background:#fff; border:1px solid #e5e9f0; border-radius:10px; padding:.55rem .75rem; }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.order-view-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const row  = document.getElementById('detail-' + this.dataset.id);
        const open = row.style.display !== 'none';

        // Accordion: close any other open detail row.
        document.querySelectorAll('.order-detail').forEach(r => { if (r !== row) r.style.display = 'none'; });
        document.querySelectorAll('.order-view-btn').forEach(b => {
            if (b !== this) { b.classList.remove('active'); b.innerHTML = '<i class="fas fa-eye me-1"></i>View'; }
        });

        row.style.display = open ? 'none' : 'table-row';
        this.classList.toggle('active', !open);
        this.innerHTML = open ? '<i class="fas fa-eye me-1"></i>View' : '<i class="fas fa-eye-slash me-1"></i>Hide';
    });
});
</script>
@endpush
