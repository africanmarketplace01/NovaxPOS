@extends('superadmin.layout.main')
@section('title', $contract->getDocumentTypeLabel())
@section('page-title', $contract->getDocumentTypeLabel())

@section('content')

@if(session('message'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('not_permitted'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('not_permitted') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="{{ route('superadmin.contracts.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>All Contracts
    </a>
    <a href="{{ route('superadmin.contracts.create', ['tenant_id' => $contract->tenant_id]) }}" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-plus me-1"></i>New Contract
    </a>
    {{-- Regenerate PDF --}}
    <form action="{{ route('superadmin.contracts.generate-pdf', $contract->id) }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-sync me-1"></i>Regenerate PDF
        </button>
    </form>
    {{-- Download / Print --}}
    @if($contract->pdf_path)
    <a href="{{ route('superadmin.contracts.download', $contract->id) }}" target="_blank" class="btn btn-sm btn-danger">
        <i class="fas fa-file-pdf me-1"></i>View / Print PDF
    </a>
    @endif
    {{-- Delete --}}
    <form action="{{ route('superadmin.contracts.destroy', $contract->id) }}" method="POST" class="d-inline"
          onsubmit="return confirm('Delete this contract permanently?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-trash me-1"></i>Delete
        </button>
    </form>
</div>

<div class="row g-3">
    {{-- Contract summary --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-file-contract me-2"></i>Contract Info</div>
            <div class="card-body" style="font-size:.875rem;">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">Type</dt>
                    <dd class="col-7">
                        @php $b = match($contract->document_type){ 'full_contract'=>'primary','sla_only'=>'info','sow_only'=>'warning',default=>'secondary' }; @endphp
                        <span class="badge bg-{{ $b }}">{{ $contract->getDocumentTypeLabel() }}</span>
                    </dd>

                    <dt class="col-5 text-muted">Tenant</dt>
                    <dd class="col-7">
                        <a href="{{ route('superadmin.clients.show', $contract->tenant_id) }}">
                            {{ $contract->tenant->company_name ?? $contract->tenant_id }}
                        </a>
                    </dd>

                    <dt class="col-5 text-muted">Effective</dt>
                    <dd class="col-7">{{ $contract->effective_date?->format('d M Y') }}</dd>

                    @if($contract->contract_amount)
                    <dt class="col-5 text-muted">Amount</dt>
                    <dd class="col-7 fw-semibold">${{ number_format($contract->contract_amount, 2) }}</dd>

                    <dt class="col-5 text-muted">Deposit</dt>
                    <dd class="col-7">{{ $contract->deposit_percent }}% — ${{ number_format($contract->getDepositAmount(), 2) }}</dd>

                    <dt class="col-5 text-muted">Balance</dt>
                    <dd class="col-7">{{ $contract->balance_percent }}% — ${{ number_format($contract->getBalanceAmount(), 2) }}</dd>
                    @endif

                    <dt class="col-5 text-muted">Timeline</dt>
                    <dd class="col-7">{{ $contract->timeline_days }} days</dd>

                    <dt class="col-5 text-muted">Hosting</dt>
                    <dd class="col-7">{{ $contract->hosting_months }} months</dd>

                    <dt class="col-5 text-muted">Warranty</dt>
                    <dd class="col-7">{{ $contract->warranty_days }} days</dd>

                    <dt class="col-5 text-muted">Support</dt>
                    <dd class="col-7">{{ $contract->support_months }} months</dd>

                    <dt class="col-5 text-muted">PDF</dt>
                    <dd class="col-7">
                        @if($contract->pdf_path)
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>Generated</span>
                        @else
                            <span class="text-muted">Not generated</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Sent</dt>
                    <dd class="col-7">
                        @if($contract->sent_at)
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $contract->sent_at->format('d M Y') }}</span>
                            <div style="font-size:.76rem;color:#94a3b8;">{{ $contract->sent_to_email }}</div>
                        @else
                            <span class="text-muted">Not sent</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Created by</dt>
                    <dd class="col-7" style="font-size:.8rem;">{{ $contract->created_by ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Created</dt>
                    <dd class="col-7">{{ $contract->created_at->format('d M Y H:i') }}</dd>
                </dl>
            </div>
        </div>

        @if($contract->notes)
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-sticky-note me-2"></i>Notes</div>
            <div class="card-body" style="font-size:.875rem;">{{ $contract->notes }}</div>
        </div>
        @endif
    </div>

    {{-- Right column: scope + email --}}
    <div class="col-lg-8">

        @if($contract->include_scope && !empty($contract->scope_items))
        <div class="card mb-3">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-list-check me-2 text-warning"></i>Scope of Work Items</div>
            <div class="card-body">
                <ol style="font-size:.875rem;margin-bottom:0;">
                    @foreach($contract->scope_items as $item)
                        <li class="mb-1">{{ $item }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
        @endif

        {{-- Email form --}}
        <div class="card">
            <div class="card-header py-2 fw-semibold"><i class="fas fa-paper-plane me-2 text-success"></i>Email Contract to Tenant</div>
            <div class="card-body">
                @if(!$contract->pdf_path)
                    <div class="alert alert-warning mb-3" style="font-size:.875rem;">
                        <i class="fas fa-exclamation-triangle me-2"></i>PDF has not been generated yet. Click "Regenerate PDF" above first, or it will be auto-generated when you send.
                    </div>
                @endif
                <form action="{{ route('superadmin.contracts.email', $contract->id) }}" method="POST">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label class="form-label">Recipient Email</label>
                            <input type="email" name="send_to_email" class="form-control"
                                   value="{{ old('send_to_email', $contract->tenant->email ?? '') }}"
                                   placeholder="tenant@email.com" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-paper-plane me-1"></i>Send Email
                            </button>
                        </div>
                    </div>
                    <div class="form-text mt-1">The PDF will be attached to the email. Tenant's registered email is pre-filled.</div>
                </form>

                @if($contract->sent_at)
                <hr class="my-3">
                <div style="font-size:.82rem;color:#64748b;">
                    <i class="fas fa-history me-1"></i>Last sent {{ $contract->sent_at->diffForHumans() }} to <strong>{{ $contract->sent_to_email }}</strong>
                </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
