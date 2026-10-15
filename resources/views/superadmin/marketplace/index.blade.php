@extends('superadmin.layout.main')
@section('title', 'Marketplace')
@section('page-title', 'Marketplace Dashboard')

@section('content')
{{-- Stats --}}
<div class="row g-3 mb-4">
    @php
    $cards = [
        ['label'=>'Active Listings','value'=>number_format($stats['active_listings']),'icon'=>'fa-box-open','color'=>'primary'],
        ['label'=>'Total Orders','value'=>number_format($stats['total_orders']),'icon'=>'fa-shopping-bag','color'=>'info'],
        ['label'=>'Pending Orders','value'=>number_format($stats['pending_orders']),'icon'=>'fa-clock','color'=>'warning'],
        ['label'=>'Platform Revenue','value'=>'$'.number_format($stats['total_revenue'],2),'icon'=>'fa-dollar-sign','color'=>'success'],
        ['label'=>'Total Sales Vol.','value'=>'$'.number_format($stats['total_sales'],2),'icon'=>'fa-chart-line','color'=>'secondary'],
        ['label'=>'Registered Shops','value'=>number_format($stats['shops']),'icon'=>'fa-store','color'=>'primary'],
        ['label'=>'Delivery Agencies','value'=>number_format($stats['agencies']),'icon'=>'fa-truck','color'=>'info'],
        ['label'=>'Pending Agencies','value'=>number_format($stats['pending_agencies']),'icon'=>'fa-user-clock','color'=>'warning'],
    ];
    @endphp
    @foreach($cards as $c)
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:10px;background:var(--bs-{{ $c['color'] }},#0d6efd)20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="fas {{ $c['icon'] }} text-{{ $c['color'] }}"></i>
                </div>
                <div>
                    <div class="fs-5 fw-bold">{{ $c['value'] }}</div>
                    <div class="text-muted small">{{ $c['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-4">
    {{-- Revenue chart --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3">
                <h6 class="mb-0 fw-semibold">Platform Revenue (Last 6 Months)</h6>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="80"></canvas>
            </div>
        </div>
    </div>

    {{-- Quick links --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header py-3"><h6 class="mb-0 fw-semibold">Quick Actions</h6></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('superadmin.marketplace.agencies') }}" class="btn btn-outline-primary btn-sm text-start">
                    <i class="fas fa-truck me-2"></i>Manage Delivery Agencies
                    @if($stats['pending_agencies'] > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $stats['pending_agencies'] }} pending</span>
                    @endif
                </a>
                <a href="{{ route('superadmin.marketplace.fees') }}" class="btn btn-outline-success btn-sm text-start">
                    <i class="fas fa-percent me-2"></i>Configure Platform Fees
                </a>
                <a href="{{ route('superadmin.marketplace.listings') }}" class="btn btn-outline-info btn-sm text-start">
                    <i class="fas fa-box-open me-2"></i>Review Listings
                </a>
                <a href="{{ route('superadmin.marketplace.orders') }}" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="fas fa-shopping-bag me-2"></i>All Orders
                </a>
                <a href="{{ route('marketplace.index') }}" target="_blank" class="btn btn-outline-dark btn-sm text-start">
                    <i class="fas fa-external-link-alt me-2"></i>View Marketplace →
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Recent orders --}}
<div class="card mt-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">Recent Marketplace Orders</h6>
        <a href="{{ route('superadmin.marketplace.orders') }}" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order #</th><th>Customer</th><th>Total</th><th>Platform Fee</th><th>Payment</th><th>Status</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $o)
                <tr>
                    <td><span class="badge bg-light text-dark border font-monospace">{{ $o->order_number }}</span></td>
                    <td>{{ $o->customer_name }}<br><small class="text-muted">{{ $o->customer_email }}</small></td>
                    <td class="fw-semibold">{{ $o->currency }} {{ number_format($o->total,2) }}</td>
                    <td class="text-success fw-semibold">+{{ number_format($o->platform_fee,2) }}</td>
                    <td><span class="badge bg-{{ $o->paymentBadge() }}">{{ ucfirst($o->payment_status) }}</span></td>
                    <td><span class="badge bg-{{ $o->statusBadge() }}">{{ ucfirst($o->status) }}</span></td>
                    <td class="text-muted small">{{ $o->created_at->format('d M H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($revenueChart->pluck('month')) !!},
        datasets: [{
            label: 'Platform Revenue ($)',
            data: {!! json_encode($revenueChart->pluck('revenue')) !!},
            backgroundColor: 'rgba(13,110,253,.2)',
            borderColor: 'rgba(13,110,253,1)',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } }
});
</script>
@endpush
