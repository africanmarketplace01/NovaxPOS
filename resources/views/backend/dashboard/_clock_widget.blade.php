@php
    /*
     * Dashboard clock in/out widget — shown only to users with a linked
     * employee record. Self-contained: renders nothing when not applicable.
     */
    $cwEmployee   = null;
    $cwOpenRecord = null;
    $cwLastOut    = null;
    try {
        $cwEmployee = \App\Models\Employee::where('user_id', auth()->id())->first();
        if ($cwEmployee) {
            $cwToday = now()->format('Y-m-d');
            $cwOpenRecord = \App\Models\Attendance::whereDate('date', $cwToday)
                ->where('employee_id', $cwEmployee->id)
                ->where(function ($q) { $q->whereNull('checkout')->orWhere('checkout', ''); })
                ->orderByDesc('id')
                ->first();
            if (! $cwOpenRecord) {
                $cwLastOut = \App\Models\Attendance::whereDate('date', $cwToday)
                    ->where('employee_id', $cwEmployee->id)
                    ->orderByDesc('id')
                    ->first();
            }
        }
    } catch (\Throwable $e) {
        $cwEmployee = null; // attendance tables unavailable — hide widget
    }
@endphp

@if($cwEmployee)
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;
                background:#fff;border:1px solid #e4e6fc;border-radius:12px;padding:14px 20px;margin-bottom:1rem;">
        <div style="display:flex;align-items:center;gap:12px;">
            <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;
                        background:{{ $cwOpenRecord ? 'rgba(40,167,69,.12)' : 'rgba(108,117,125,.12)' }};
                        color:{{ $cwOpenRecord ? '#28a745' : '#6c757d' }};">
                <i class="fas fa-user-clock"></i>
            </div>
            <div>
                <div style="font-weight:700;font-size:.9rem;">
                    @if($cwOpenRecord)
                        Clocked in — {{ $cwOpenRecord->checkin }}
                    @elseif($cwLastOut)
                        Shift ended — {{ $cwLastOut->checkin }} → {{ $cwLastOut->checkout }}
                    @else
                        Not clocked in yet
                    @endif
                </div>
                <div style="font-size:.75rem;color:#6b7280;">{{ now()->format('l, d M Y') }}</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            @if($cwOpenRecord)
                <form action="{{ route('attendance.selfCheckout') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm" style="font-weight:600;">
                        <i class="fas fa-right-from-bracket" style="margin-right:4px;"></i>Clock Out
                    </button>
                </form>
            @else
                <form action="{{ route('attendance.selfCheckin') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm" style="font-weight:600;">
                        <i class="fas fa-right-to-bracket" style="margin-right:4px;"></i>Clock In
                    </button>
                </form>
            @endif
            <a href="{{ route('attendance.self') }}" class="btn btn-link btn-sm" style="font-size:.78rem;">
                View my attendance
            </a>
        </div>
    </div>
@endif
