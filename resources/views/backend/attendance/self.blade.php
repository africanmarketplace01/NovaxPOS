@extends('backend.layout.main')

@section('content')

<x-success-message key="message" />
<x-error-message   key="not_permitted" />

<section>
<div class="container-fluid">

    {{-- ── Header ──────────────────────────────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col">
            <h3 class="mb-0">
                <i class="dripicons-time-reverse"></i>
                My Attendance — Self Clock In / Out
            </h3>
            <p class="text-muted mb-0">{{ $employee->name }} &bull; {{ date('l, d F Y') }}</p>
        </div>
    </div>

    {{-- ── Today's Status & Action Buttons ─────────────────────────────────────── --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center py-4">
                    <div id="live-clock" style="font-size:2.5rem;font-weight:700;letter-spacing:2px;font-variant-numeric:tabular-nums;"></div>
                    <p class="text-muted mb-3">{{ date('l, d F Y') }}</p>

                    @php
                        $openRecord = $todayRecords->whereNull('checkout')->first();
                        $alreadyCheckedIn = $todayRecords->count() > 0;
                    @endphp

                    <div class="d-flex justify-content-center gap-3" style="gap:12px;">
                        <form action="{{ route('attendance.selfCheckin') }}" method="POST" class="mr-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg px-4"
                                {{ $openRecord ? 'disabled' : '' }}>
                                <i class="dripicons-arrow-thin-right"></i> Clock In
                            </button>
                        </form>
                        <form action="{{ route('attendance.selfCheckout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-lg px-4"
                                {{ !$openRecord ? 'disabled' : '' }}>
                                <i class="dripicons-arrow-thin-left"></i> Clock Out
                            </button>
                        </form>
                    </div>

                    @if($openRecord)
                        <p class="text-success mt-3 mb-0">
                            <i class="dripicons-checkmark"></i>
                            Clocked in at <strong>{{ $openRecord->checkin }}</strong>
                        </p>
                    @elseif($todayRecords->count() > 0)
                        <p class="text-muted mt-3 mb-0">
                            <i class="dripicons-checkmark"></i>
                            Last shift ended — {{ $todayRecords->last()->checkout }}
                        </p>
                    @else
                        <p class="text-muted mt-3 mb-0">Not clocked in yet today.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Today's Records ─────────────────────────────────────────────────── --}}
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><strong>Today's Clock Records</strong></div>
                <div class="card-body p-0">
                    @if($todayRecords->count())
                        <table class="table table-sm table-striped mb-0">
                            <thead><tr><th>#</th><th>In</th><th>Out</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($todayRecords as $i => $rec)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $rec->checkin ?? '—' }}</td>
                                    <td>{{ $rec->checkout ?? '<span class="badge badge-warning">Open</span>' }}</td>
                                    <td>
                                        @if($rec->status)
                                            <span class="badge badge-success">On Time</span>
                                        @else
                                            <span class="badge badge-danger">Late</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted text-center py-3">No records for today.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── 30-day History ───────────────────────────────────────────────────────── --}}
    <div class="card shadow-sm">
        <div class="card-header"><strong>Recent Attendance (Last 30 Days)</strong></div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Status</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($history as $att)
                    <tr>
                        <td>{{ date('d M Y', strtotime($att->date)) }}</td>
                        <td>{{ $att->checkin ?? '—' }}</td>
                        <td>{{ $att->checkout ?? '—' }}</td>
                        <td>
                            @if($att->status)
                                <span class="badge badge-success">On Time</span>
                            @else
                                <span class="badge badge-danger">Late</span>
                            @endif
                        </td>
                        <td>{{ $att->note ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No attendance history yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</section>

@endsection

@push('scripts')
<script>
    // Live clock
    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('live-clock').textContent = h + ':' + m + ':' + s;
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>
@endpush
