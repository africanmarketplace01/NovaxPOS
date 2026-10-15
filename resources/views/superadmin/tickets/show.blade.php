@extends('superadmin.layout.main')
@section('title', 'Ticket #' . $ticket->id)
@section('page-title', 'Support Ticket')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('superadmin.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
    <div class="d-flex gap-2">
        @if($ticket->status !== 'closed')
        <form action="{{ route('superadmin.tickets.update-status', $ticket->id) }}" method="POST">
            @csrf @method('PATCH')
            <input type="hidden" name="status" value="closed">
            <button class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i>Close Ticket</button>
        </form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header py-3"><i class="fas fa-info-circle me-2"></i>Ticket Info</div>
            <div class="card-body">
                <dl class="row mb-0" style="font-size:.9rem;">
                    <dt class="col-5 text-muted">ID</dt>
                    <dd class="col-7">#{{ $ticket->id }}</dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        @php $sc = ['open'=>'danger','in_progress'=>'warning','closed'=>'success'][$ticket->status] ?? 'secondary'; @endphp
                        <span class="badge bg-{{ $sc }}">{{ ucwords(str_replace('_', ' ', $ticket->status)) }}</span>
                    </dd>

                    <dt class="col-5 text-muted">Priority</dt>
                    <dd class="col-7">
                        @php $pc = ['low'=>'secondary','medium'=>'warning','high'=>'danger'][$ticket->priority] ?? 'secondary'; @endphp
                        <span class="badge bg-{{ $pc }}">{{ ucfirst($ticket->priority) }}</span>
                    </dd>

                    <dt class="col-5 text-muted">Tenant</dt>
                    <dd class="col-7">{{ $tenant?->company_name ?? ($ticket->tenant_id ?? '—') }}</dd>

                    <dt class="col-5 text-muted">From</dt>
                    <dd class="col-7">{{ $ticket->submitter_name }}<br>
                        <small class="text-muted">{{ $ticket->submitter_email }}</small>
                    </dd>

                    <dt class="col-5 text-muted">Date</dt>
                    <dd class="col-7">{{ \Carbon\Carbon::parse($ticket->created_at)->format('d M Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Original message -->
        <div class="card mb-3">
            <div class="card-header py-3 fw-semibold">{{ $ticket->subject }}</div>
            <div class="card-body">
                <p class="mb-0" style="white-space:pre-wrap;">{{ $ticket->message }}</p>
            </div>
        </div>

        <!-- Replies -->
        @foreach($replies as $reply)
        <div class="card mb-2 {{ $reply->sender_type === 'admin' ? 'border-primary' : '' }}">
            <div class="card-header py-2 d-flex justify-content-between"
                 style="{{ $reply->sender_type === 'admin' ? 'background:#eff6ff;' : '' }}">
                <span class="fw-semibold" style="font-size:.85rem;">
                    @if($reply->sender_type === 'admin')
                        <i class="fas fa-user-shield text-primary me-1"></i>Admin
                    @else
                        <i class="fas fa-user me-1"></i>{{ $ticket->submitter_name ?? 'Client' }}
                    @endif
                </span>
                <small class="text-muted">{{ \Carbon\Carbon::parse($reply->created_at)->format('d M Y H:i') }}</small>
            </div>
            <div class="card-body py-2">
                <p class="mb-0" style="white-space:pre-wrap;font-size:.9rem;">{{ $reply->message }}</p>
            </div>
        </div>
        @endforeach

        <!-- Reply Form -->
        @if($ticket->status !== 'closed')
        <div class="card mt-3">
            <div class="card-header py-3"><i class="fas fa-reply me-2"></i>Reply</div>
            <div class="card-body">
                <form action="{{ route('superadmin.tickets.reply', $ticket->id) }}" method="POST">
                    @csrf
                    <textarea name="reply" class="form-control mb-3" rows="4" placeholder="Type your reply…" required></textarea>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Send Reply
                    </button>
                </form>
            </div>
        </div>
        @else
        <div class="alert alert-secondary mt-3">
            <i class="fas fa-lock me-2"></i>This ticket is closed.
        </div>
        @endif
    </div>
</div>
@endsection
