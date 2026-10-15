@extends('backend.layout.main')

@section('content')

<x-success-message key="message" />
<x-error-message   key="not_permitted" />

<section>
<div class="container-fluid">

    {{-- ── Page Header ──────────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="dripicons-document"></i> Audit Report (3.12)</h3>
        <small class="text-muted">By date, sales volume, revenue &amp; user activity</small>
    </div>

    {{-- ── Filter Form ──────────────────────────────────────────────────────────── --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('report.audit') }}" class="form-row align-items-end">
                <div class="form-group col-md-2">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="form-group col-md-2">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="form-group col-md-2">
                    <label>User</label>
                    <select name="user_id" class="form-control selectpicker" data-live-search="true">
                        <option value="">— All Users —</option>
                        @foreach($lims_user_list as $u)
                            <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>Min Revenue</label>
                    <input type="number" name="min_revenue" class="form-control" value="{{ $minRevenue }}" placeholder="0.00" step="0.01">
                </div>
                <div class="form-group col-md-2">
                    <label>Max Revenue</label>
                    <input type="number" name="max_revenue" class="form-control" value="{{ $maxRevenue }}" placeholder="Any" step="0.01">
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="dripicons-search"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Revenue KPI Cards ────────────────────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white text-center py-3 shadow-sm">
                <div style="font-size:1.8rem;font-weight:700;">{{ number_format($totalRevenue, 2) }}</div>
                <div>Total Revenue</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white text-center py-3 shadow-sm">
                <div style="font-size:1.8rem;font-weight:700;">{{ $totalSalesCount }}</div>
                <div>Total Transactions</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white text-center py-3 shadow-sm">
                <div style="font-size:1.8rem;font-weight:700;">{{ number_format($totalPaid, 2) }}</div>
                <div>Total Paid</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white text-center py-3 shadow-sm">
                <div style="font-size:1.8rem;font-weight:700;">{{ number_format($totalDue, 2) }}</div>
                <div>Total Due</div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- ── Daily Sales Volume ───────────────────────────────────────────────── --}}
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between">
                    <strong>Daily Sales (by date)</strong>
                    <span class="text-muted small">{{ $startDate }} → {{ $endDate }}</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0" id="daily-table">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th class="text-right">Transactions</th>
                                <th class="text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($dailySales as $day)
                            <tr>
                                <td>{{ date('d M Y', strtotime($day->date)) }}</td>
                                <td class="text-right">{{ $day->count }}</td>
                                <td class="text-right">{{ number_format($day->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No sales in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Activity Log ─────────────────────────────────────────────────── --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header"><strong>Activity Log</strong> <span class="text-muted small">(latest 200)</span></div>
                <div class="card-body p-0" style="max-height:320px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>Date/Time</th><th>User</th><th>Action</th><th>Reference</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                        @forelse($activityLogs as $log)
                            <tr>
                                <td nowrap>{{ date('d M Y H:i', strtotime($log->date)) }}</td>
                                <td>{{ $log->user_name ?? '—' }}</td>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->reference_no }}</td>
                                <td>{{ $log->item_description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No activity in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── User Activity Summary ────────────────────────────────────────────── --}}
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><strong>User Activity</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="thead-light">
                            <tr><th>User</th><th class="text-right">Sales</th><th class="text-right">Revenue</th></tr>
                        </thead>
                        <tbody>
                        @forelse($userActivity as $ua)
                            <tr>
                                <td>{{ $ua->user_name }}</td>
                                <td class="text-right">{{ $ua->sale_count }}</td>
                                <td class="text-right">{{ number_format($ua->total_revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No data.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Print / Export ────────────────────────────────────────────────── --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-block mb-2">
                        <i class="dripicons-print"></i> Print Report
                    </button>
                    <a href="{{ route('report.audit') }}?start_date={{ $startDate }}&end_date={{ $endDate }}&user_id={{ $userId }}&min_revenue={{ $minRevenue }}&max_revenue={{ $maxRevenue }}"
                       class="btn btn-outline-primary btn-block">
                        <i class="dripicons-arrow-down"></i> Refresh
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
</section>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        $('#daily-table').DataTable({ paging: true, searching: false, ordering: true, pageLength: 15 });
    });
</script>
@endpush
