@extends('superadmin.layout.main')
@section('title', 'Delivery Agencies')
@section('page-title', 'Delivery Agencies')

@section('content')
{{-- Filter tabs --}}
<div class="d-flex gap-2 mb-3 flex-wrap">
    @foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','suspended'=>'Suspended'] as $val=>$label)
    <a href="{{ route('superadmin.marketplace.agencies', ['status'=>$val]) }}"
       class="btn btn-sm {{ $status===$val ? 'btn-primary' : 'btn-outline-secondary' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Agency</th><th>Contact</th><th>Coverage</th><th>Base Rate</th><th>Est. Time</th><th>Status</th><th>Registered</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agencies as $ag)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $ag->name }}</div>
                        <small class="text-muted">{{ $ag->email }}</small>
                    </td>
                    <td>
                        {{ $ag->contact_name }}<br>
                        <small class="text-muted">{{ $ag->phone }}</small>
                    </td>
                    <td style="max-width:180px"><small>{{ Str::limit($ag->coverage_areas,60) }}</small></td>
                    <td>{{ $ag->currency }} {{ number_format($ag->base_rate,2) }}</td>
                    <td><small>{{ $ag->deliveryEstimate() }}</small></td>
                    <td>
                        @php $colors=['pending'=>'warning','approved'=>'success','rejected'=>'danger','suspended'=>'secondary']; @endphp
                        <span class="badge bg-{{ $colors[$ag->status] ?? 'secondary' }}">{{ ucfirst($ag->status) }}</span>
                    </td>
                    <td class="text-muted small">{{ $ag->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            @if($ag->status !== 'approved')
                            <form action="{{ route('superadmin.marketplace.agency.approve', $ag->id) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-success" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            @endif
                            @if(in_array($ag->status, ['pending','approved']))
                            <button class="btn btn-sm btn-danger" onclick="showReject({{ $ag->id }})" title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                            @endif
                            @if($ag->status === 'approved')
                            <form action="{{ route('superadmin.marketplace.agency.suspend', $ag->id) }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-warning" title="Suspend">
                                    <i class="fas fa-pause"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No agencies found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $agencies->withQueryString()->links() }}</div>
</div>

{{-- Reject modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h6 class="modal-title fw-bold">Reject Agency</h6></div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label fw-semibold" style="font-size:.875rem">Reason (optional)</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Reason for rejection…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showReject(id) {
    document.getElementById('rejectForm').action = `/superadmin/marketplace/agencies/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
