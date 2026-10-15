@extends('superadmin.layout.main')
@section('title', 'Marketplace Reports')
@section('page-title', 'Marketplace Reports')

@section('content')

{{-- ── Period filter ── --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-center flex-wrap gap-2">
            @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year'] as $p => $label)
                <a href="{{ route('superadmin.marketplace.reports', ['period' => $p]) }}"
                   class="btn btn-sm {{ $preset === $p ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
            <span class="mx-1 text-muted">or</span>
            <input type="hidden" name="period" value="range">
            <input type="date" name="from" value="{{ request('from', $from->toDateString()) }}" class="form-control form-control-sm" style="width:150px;">
            <input type="date" name="to" value="{{ request('to', $to->toDateString()) }}" class="form-control form-control-sm" style="width:150px;">
            <button class="btn btn-sm btn-primary">Apply Range</button>
            <span class="ms-auto text-muted small">Showing {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</span>
        </form>
    </div>
</div>

{{-- ── Summary cards ── --}}
<div class="row g-3 mb-3">
    @php
        $cards = [
            ['Orders', number_format($summary['orders']), 'fa-cart-shopping', '#3b82f6'],
            ['Gross Sales', $summary['currency'] . ' ' . number_format($summary['sales'], 2), 'fa-coins', '#22c55e'],
            ['Platform Fees', $summary['currency'] . ' ' . number_format($summary['platform_fees'], 2), 'fa-percent', '#8b5cf6'],
            ['Delivery Fees', $summary['currency'] . ' ' . number_format($summary['delivery_fees'], 2), 'fa-truck', '#f59e0b'],
            ['Fees Collected (paid)', $summary['currency'] . ' ' . number_format($summary['paid_fees'], 2), 'fa-circle-check', '#16a34a'],
            ['Fees Outstanding', $summary['currency'] . ' ' . number_format($summary['unpaid_fees'], 2), 'fa-circle-exclamation', '#dc2626'],
        ];
    @endphp
    @foreach($cards as [$label, $value, $icon, $color])
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span style="width:30px;height:30px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;background:{{ $color }}1a;color:{{ $color }};"><i class="fas {{ $icon }}"></i></span>
                    <small class="text-muted">{{ $label }}</small>
                </div>
                <div class="fw-bold" style="font-size:1.05rem;">{{ $value }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Charts ── --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header fw-semibold">Revenue &amp; Platform Fees</div>
            <div class="card-body"><canvas id="revChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header fw-semibold">Sales by Payment Method</div>
            <div class="card-body"><canvas id="payChart" height="220"></canvas></div>
        </div>
    </div>
</div>

{{-- ── Vendor revenue ── --}}
<div class="card mb-3">
    <div class="card-header fw-semibold">Revenue by Vendor</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Vendor</th><th class="text-center">Orders</th><th class="text-end">Sales</th><th class="text-end">Platform Fee Share</th></tr>
            </thead>
            <tbody>
                @forelse($vendors as $v)
                <tr>
                    <td>{{ $v['tenant_name'] }} <small class="text-muted">({{ $v['tenant_id'] }})</small></td>
                    <td class="text-center">{{ $v['orders'] }}</td>
                    <td class="text-end">{{ $v['currency'] }} {{ number_format($v['subtotal'], 2) }}</td>
                    <td class="text-end fw-semibold">{{ $v['currency'] }} {{ number_format($v['fee_share'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">No orders in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ── Platform fee requests ── --}}
<div class="card mb-3">
    <div class="card-header fw-semibold"><i class="fas fa-file-invoice-dollar me-2"></i>Platform Fee Requests</div>
    <div class="card-body">
        <p class="text-muted small mb-2">Bills vendors for <strong>cash-on-delivery</strong> orders, where they collected the money: the customer fee share plus any marketplace-seller commission. Online-paid orders are settled through <strong>Vendor Payouts</strong> below instead. Requests are created as drafts; review then send.</p>
        <form action="{{ route('superadmin.marketplace.fee-requests.generate') }}" method="POST" class="d-flex align-items-end flex-wrap gap-2 mb-3">
            @csrf
            <div><label class="form-label small text-muted mb-0">From</label>
                <input type="date" name="period_start" value="{{ $from->toDateString() }}" class="form-control form-control-sm" required></div>
            <div><label class="form-label small text-muted mb-0">To</label>
                <input type="date" name="period_end" value="{{ $to->toDateString() }}" class="form-control form-control-sm" required></div>
            <div><label class="form-label small text-muted mb-0">Vendor</label>
                <select name="tenant_id" class="form-select form-select-sm">
                    <option value="">All vendors</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}">{{ $t->company_name }}</option>
                    @endforeach
                </select></div>
            <button class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Generate Drafts</button>
        </form>

        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Ref</th><th>Vendor</th><th>Period</th><th class="text-center">Orders</th><th class="text-end">Sales</th><th class="text-end">Fee</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($feeRequests as $fr)
                    <tr>
                        <td class="fw-semibold">{{ $fr->reference }}</td>
                        <td>{{ $fr->tenant_name }}</td>
                        <td><small>{{ \Carbon\Carbon::parse($fr->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($fr->period_end)->format('d M Y') }}</small></td>
                        <td class="text-center">{{ $fr->orders_count }}</td>
                        <td class="text-end">{{ number_format($fr->sales_subtotal, 2) }}</td>
                        <td class="text-end fw-semibold">{{ $fr->currency }} {{ number_format($fr->fee_amount, 2) }}</td>
                        <td>
                            @php $badge = ['draft' => 'secondary', 'sent' => 'warning', 'paid' => 'success', 'cancelled' => 'dark'][$fr->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $badge }}">{{ ucfirst($fr->status) }}</span>
                        </td>
                        <td class="text-end" style="white-space:nowrap;">
                            @if($fr->status === 'draft')
                                <form action="{{ route('superadmin.marketplace.fee-requests.send', $fr->id) }}" method="POST" class="d-inline">@csrf
                                    <button class="btn btn-sm btn-outline-primary" title="Email invoice + notify vendor"><i class="fas fa-paper-plane"></i> Send</button>
                                </form>
                            @endif
                            @if($fr->status === 'sent')
                                <form action="{{ route('superadmin.marketplace.fee-requests.status', $fr->id) }}" method="POST" class="d-inline">@csrf
                                    <input type="hidden" name="status" value="paid">
                                    <button class="btn btn-sm btn-outline-success" title="Mark as paid"><i class="fas fa-check"></i> Paid</button>
                                </form>
                            @endif
                            @if(in_array($fr->status, ['draft', 'sent']))
                                <form action="{{ route('superadmin.marketplace.fee-requests.status', $fr->id) }}" method="POST" class="d-inline">@csrf
                                    <input type="hidden" name="status" value="cancelled">
                                    <button class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fas fa-times"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No fee requests yet — generate drafts above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Vendor payouts ── --}}
<div class="card mb-3">
    <div class="card-header fw-semibold"><i class="fas fa-money-bill-transfer me-2"></i>Vendor Payouts</div>
    <div class="card-body">
        <p class="text-muted small mb-2">
            Money the platform owes vendors for <strong>online-paid</strong> orders (marked paid, not cash on delivery):
            the vendor's item value minus any marketplace-seller commission. The customer fee and delivery fee stay with the platform and agency.
            Each order is only ever included in one payout. Create drafts, send the money, then mark each one paid — the vendor is notified.
        </p>
        <form action="{{ route('superadmin.marketplace.payouts.generate') }}" method="POST" class="d-flex align-items-end flex-wrap gap-2 mb-3">
            @csrf
            <div><label class="form-label small text-muted mb-0">From</label>
                <input type="date" name="period_start" value="{{ $from->toDateString() }}" class="form-control form-control-sm" required></div>
            <div><label class="form-label small text-muted mb-0">To</label>
                <input type="date" name="period_end" value="{{ $to->toDateString() }}" class="form-control form-control-sm" required></div>
            <div><label class="form-label small text-muted mb-0">Vendor</label>
                <select name="tenant_id" class="form-select form-select-sm">
                    <option value="">All vendors</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}">{{ $t->company_name }}</option>
                    @endforeach
                </select></div>
            <button class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Create Payout Drafts</button>
        </form>

        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Ref</th><th>Vendor</th><th>Period</th><th class="text-center">Orders</th><th class="text-end">Sales</th><th class="text-end">Commission</th><th class="text-end">To pay</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($payouts as $po)
                    <tr>
                        <td class="fw-semibold">{{ $po->reference }}</td>
                        <td>{{ $po->tenant_name }}</td>
                        <td><small>{{ \Carbon\Carbon::parse($po->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($po->period_end)->format('d M Y') }}</small></td>
                        <td class="text-center">{{ $po->orders_count }}</td>
                        <td class="text-end">{{ number_format($po->gross_amount, 2) }}</td>
                        <td class="text-end text-muted">{{ (float) $po->commission_amount > 0 ? '−' . number_format($po->commission_amount, 2) : '—' }}</td>
                        <td class="text-end fw-semibold">{{ $po->currency }} {{ number_format($po->net_amount, 2) }}</td>
                        <td>
                            @php $badge = ['draft' => 'warning', 'paid' => 'success', 'cancelled' => 'dark'][$po->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $badge }}">{{ $po->status === 'draft' ? 'To pay' : ucfirst($po->status) }}</span>
                            @if($po->status === 'paid')
                                <div class="small text-muted">{{ strtoupper($po->payout_method) }}{{ $po->payout_reference ? ' · ' . $po->payout_reference : '' }}</div>
                            @endif
                        </td>
                        <td class="text-end" style="white-space:nowrap;">
                            @if($po->status === 'draft')
                                <form action="{{ route('superadmin.marketplace.payouts.update', $po->id) }}" method="POST" class="d-inline-flex gap-1 align-items-center">@csrf
                                    <input type="hidden" name="action" value="paid">
                                    <select name="payout_method" class="form-select form-select-sm" style="width:auto" required>
                                        <option value="bank">Bank</option>
                                        <option value="mtn">MTN MoMo</option>
                                        <option value="orange">Orange Money</option>
                                        <option value="cash">Cash</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <input type="text" name="payout_reference" class="form-control form-control-sm" placeholder="Transfer ref." style="width:9rem">
                                    <button class="btn btn-sm btn-outline-success" title="Record that the money was sent"><i class="fas fa-check"></i> Paid</button>
                                </form>
                                <form action="{{ route('superadmin.marketplace.payouts.update', $po->id) }}" method="POST" class="d-inline">@csrf
                                    <input type="hidden" name="action" value="cancel">
                                    <button class="btn btn-sm btn-outline-danger" title="Cancel draft"><i class="fas fa-times"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No payouts yet — create drafts above.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartData = @json($chart);
const payData   = @json($byPayment);

new Chart(document.getElementById('revChart'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            { label: 'Gross Sales',   data: chartData.sales,  borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.12)', fill: true, tension: .3 },
            { label: 'Platform Fees', data: chartData.fees,   borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.10)', fill: true, tension: .3 },
            { label: 'Orders',        data: chartData.orders, borderColor: '#22c55e', borderDash: [4,3], yAxisID: 'y1', tension: .3 }
        ]
    },
    options: {
        interaction: { mode: 'index', intersect: false },
        scales: {
            y:  { beginAtZero: true },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } }
        }
    }
});

new Chart(document.getElementById('payChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(payData).map(k => k.toUpperCase()),
        datasets: [{ data: Object.values(payData),
            backgroundColor: ['#3b82f6','#22c55e','#f59e0b','#8b5cf6','#ef4444','#06b6d4'] }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});
</script>
@endpush
