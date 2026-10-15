@extends('superadmin.layout.main')
@section('title', 'Support Tickets')
@section('page-title', 'Support Tickets')

@section('content')
@if($openCount > 0)
<div class="alert alert-info d-flex align-items-center mb-3">
    <i class="fas fa-ticket-alt me-2"></i>
    <span><strong>{{ $openCount }}</strong> open ticket(s) awaiting response.</span>
</div>
@endif

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="open"        {{ request('status')=='open'?'selected':'' }}>Open</option>
                    <option value="in_progress" {{ request('status')=='in_progress'?'selected':'' }}>In Progress</option>
                    <option value="closed"      {{ request('status')=='closed'?'selected':'' }}>Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priority</option>
                    <option value="low"    {{ request('priority')=='low'?'selected':'' }}>Low</option>
                    <option value="medium" {{ request('priority')=='medium'?'selected':'' }}>Medium</option>
                    <option value="high"   {{ request('priority')=='high'?'selected':'' }}>High</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="tenant_id" class="form-select form-select-sm">
                    <option value="">All Tenants</option>
                    @foreach($allTenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id')==$t->id?'selected':'' }}>
                            {{ $t->company_name ?? $t->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">Filter</button>
                <a href="{{ route('superadmin.tickets.index') }}" class="btn btn-sm btn-outline-secondary flex-fill">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Tenant</th>
                        <th>Submitter</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr class="{{ $ticket->status === 'open' ? 'table-warning' : '' }}">
                        <td><small class="text-muted">{{ $ticket->id }}</small></td>
                        <td>
                            <a href="{{ route('superadmin.tickets.show', $ticket->id) }}" class="text-decoration-none fw-semibold">
                                {{ Str::limit($ticket->subject, 50) }}
                            </a>
                        </td>
                        <td>{{ $tenants[$ticket->tenant_id]->company_name ?? ($ticket->tenant_id ?? 'Unknown') }}</td>
                        <td>
                            <div style="font-size:.85rem;">{{ $ticket->submitter_name }}</div>
                            <small class="text-muted">{{ $ticket->submitter_email }}</small>
                        </td>
                        <td>
                            @php $pc = ['low'=>'secondary','medium'=>'warning','high'=>'danger'][$ticket->priority] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $pc }}">{{ ucfirst($ticket->priority) }}</span>
                        </td>
                        <td>
                            @php $sc = ['open'=>'danger','in_progress'=>'warning','closed'=>'success'][$ticket->status] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $sc }}">{{ ucwords(str_replace('_', ' ', $ticket->status)) }}</span>
                        </td>
                        <td style="font-size:.8rem;">{{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('superadmin.tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($ticket->status !== 'closed')
                                <form action="{{ route('superadmin.tickets.update-status', $ticket->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="closed">
                                    <button class="btn btn-sm btn-outline-success" title="Close">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('superadmin.tickets.destroy', $ticket->id) }}" method="POST"
                                      onsubmit="return confirm('Delete this ticket?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No tickets found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($tickets->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Page {{ $tickets->currentPage() }} of {{ $tickets->lastPage() }}</small>
        {{ $tickets->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
