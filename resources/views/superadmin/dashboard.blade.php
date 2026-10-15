@extends('superadmin.layout.main')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('styles')
<style>
    .stat-card.indigo  { background: linear-gradient(135deg, #6366f1, #4f46e5); }
    .stat-card.green   { background: linear-gradient(135deg, #10b981, #059669); }
    .stat-card.red     { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .stat-card.amber   { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stat-card.purple  { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .stat-card.cyan    { background: linear-gradient(135deg, #06b6d4, #0891b2); }
</style>
@endsection

@section('content')
<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card indigo d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-label">Total Clients</div>
                <div class="stat-value">{{ $totalTenants }}</div>
            </div>
            <i class="fas fa-users stat-icon"></i>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card green d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-label">Active</div>
                <div class="stat-value">{{ $activeTenants }}</div>
            </div>
            <i class="fas fa-check-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card red d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-label">Expired</div>
                <div class="stat-value">{{ $expiredTenants }}</div>
            </div>
            <i class="fas fa-times-circle stat-icon"></i>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card amber d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-label">Free Trial</div>
                <div class="stat-value">{{ $trialTenants }}</div>
            </div>
            <i class="fas fa-hourglass-half stat-icon"></i>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card purple d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-label">MRR</div>
                <div class="stat-value">${{ number_format($mrr, 0) }}</div>
            </div>
            <i class="fas fa-dollar-sign stat-icon"></i>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="stat-card cyan d-flex justify-content-between align-items-center">
            <div>
                <div class="stat-label">ARR</div>
                <div class="stat-value">${{ number_format($arr, 0) }}</div>
            </div>
            <i class="fas fa-chart-line stat-icon"></i>
        </div>
    </div>
</div>

@if($expiringSoon > 0)
<div class="alert alert-warning d-flex align-items-center mb-4">
    <i class="fas fa-exclamation-circle me-2"></i>
    <span><strong>{{ $expiringSoon }}</strong> client(s) expire within 7 days.
        <a href="{{ route('superadmin.clients.index') }}?status=1" class="alert-link">View clients →</a>
    </span>
</div>
@endif

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3">
                <i class="fas fa-user-plus me-2 text-primary"></i>New Signups (12 months)
            </div>
            <div class="card-body">
                <canvas id="signupChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-3">
                <i class="fas fa-dollar-sign me-2 text-success"></i>Revenue (12 months)
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tenants + Payments -->
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2 text-primary"></i>Recent Clients</span>
                <a href="{{ route('superadmin.clients.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Company</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTenants as $tenant)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $tenant->company_name ?? $tenant->id }}</div>
                                    <small class="text-muted">{{ $tenant->email }}</small>
                                </td>
                                <td><span class="badge bg-secondary">{{ $tenant->package->name ?? '—' }}</span></td>
                                <td>
                                    @if($tenant->status)
                                        <span class="badge badge-active">Active</span>
                                    @else
                                        <span class="badge badge-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($tenant->expiry_date)
                                        <span class="{{ $tenant->expiry_date < now() ? 'text-danger' : 'text-muted' }}" style="font-size:.8rem;">
                                            {{ \Carbon\Carbon::parse($tenant->expiry_date)->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-credit-card me-2 text-success"></i>Recent Payments</span>
                <a href="{{ route('superadmin.payments.index') }}" class="btn btn-sm btn-outline-success">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Tenant</th><th>Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentPayments as $payment)
                            <tr>
                                <td style="font-size:.85rem;">{{ $payment->tenant->company_name ?? $payment->tenant_id }}</td>
                                <td>
                                    <span class="fw-semibold">{{ strtoupper($payment->currency ?? 'USD') }}
                                        {{ number_format($payment->amount, 2) }}</span>
                                </td>
                                <td>
                                    @php $sc = ['completed'=>'success','pending'=>'warning','failed'=>'danger','refunded'=>'secondary'][$payment->status] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $sc }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $chartMonths    = $signupChart->pluck('month')->map(function($m){ return date('M', mktime(0,0,0,$m,1)); })->values();
    $chartSignups   = $signupChart->pluck('count')->values();
    $chartRevMonths = $revenueChart->pluck('month')->map(function($m){ return date('M', mktime(0,0,0,$m,1)); })->values();
    $chartRevData   = $revenueChart->pluck('total')->values();
@endphp
@section('scripts')
<script>
const months    = @json($chartMonths);
const signups   = @json($chartSignups);
const revMonths = @json($chartRevMonths);
const revData   = @json($chartRevData);

new Chart(document.getElementById('signupChart'), {
    type: 'bar',
    data: { labels: months, datasets: [{ label: 'Signups', data: signups,
        backgroundColor: 'rgba(99,102,241,.7)', borderRadius: 6 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: { labels: revMonths, datasets: [{ label: 'Revenue ($)', data: revData,
        borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', fill: true, tension: .4, borderWidth: 2 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
@endsection
