@extends('superadmin.layout.main')
@section('title', 'Contracts & SLAs')
@section('page-title', 'Contracts & SLAs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0" style="font-size:.875rem;">Generate, email, and track service contracts and SLAs for your tenants.</p>
    <a href="{{ route('superadmin.contracts.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i> New Contract
    </a>
</div>

@if(session('message'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('message') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('not_permitted'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('not_permitted') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tenant</th>
                        <th>Document Type</th>
                        <th>Effective Date</th>
                        <th>Amount</th>
                        <th>PDF</th>
                        <th>Sent</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($contracts as $c)
                    <tr>
                        <td class="text-muted" style="font-size:.82rem;">{{ $c->id }}</td>
                        <td>
                            <a href="{{ route('superadmin.clients.show', $c->tenant_id) }}" class="fw-semibold text-decoration-none">
                                {{ $c->tenant->company_name ?? $c->tenant_id }}
                            </a>
                            <div style="font-size:.76rem;color:#94a3b8;">{{ $c->tenant_id }}</div>
                        </td>
                        <td>
                            @php
                                $badge = match($c->document_type) {
                                    'full_contract' => 'primary',
                                    'sla_only'      => 'info',
                                    'sow_only'      => 'warning',
                                    default         => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $badge }}">{{ $c->getDocumentTypeLabel() }}</span>
                        </td>
                        <td>{{ $c->effective_date?->format('d M Y') }}</td>
                        <td>
                            @if($c->contract_amount)
                                <span class="fw-semibold">${{ number_format($c->contract_amount, 2) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($c->pdf_path)
                                <a href="{{ route('superadmin.contracts.download', $c->id) }}" target="_blank"
                                   class="btn btn-xs btn-outline-danger" title="View PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            @else
                                <form action="{{ route('superadmin.contracts.generate-pdf', $c->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline-secondary" title="Generate PDF">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td>
                            @if($c->sent_at)
                                <span class="text-success" style="font-size:.8rem;">
                                    <i class="fas fa-check-circle me-1"></i>{{ $c->sent_at->format('d M Y') }}
                                </span>
                            @else
                                <span class="text-muted" style="font-size:.8rem;">Not sent</span>
                            @endif
                        </td>
                        <td style="font-size:.82rem;">{{ $c->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('superadmin.contracts.show', $c->id) }}" class="btn btn-xs btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="{{ route('superadmin.contracts.destroy', $c->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this contract?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No contracts yet. <a href="{{ route('superadmin.contracts.create') }}">Create one</a>.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $contracts->links() }}</div>
@endsection
