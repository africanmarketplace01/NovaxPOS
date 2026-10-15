@extends('superadmin.layout.main')
@section('title', 'Client: ' . ($tenant->company_name ?? $tenant->id))
@section('page-title', 'Client Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('superadmin.clients.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back
    </a>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('superadmin.clients.impersonate', $tenant->id) }}" class="btn btn-sm btn-warning">
            <i class="fas fa-user-secret me-1"></i>Impersonate
        </a>
        <a href="{{ route('superadmin.clients.edit', $tenant->id) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="{{ route('superadmin.contracts.create', ['tenant_id' => $tenant->id, 'document_type' => 'full_contract']) }}"
           class="btn btn-sm btn-outline-dark">
            <i class="fas fa-file-contract me-1"></i>Generate Contract
        </a>
        <a href="{{ route('superadmin.contracts.create', ['tenant_id' => $tenant->id, 'document_type' => 'sla_only']) }}"
           class="btn btn-sm btn-outline-info">
            <i class="fas fa-shield-alt me-1"></i>Generate SLA
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Profile -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header py-3"><i class="fas fa-building me-2"></i>Tenant Info</div>
            <div class="card-body">

                {{-- Logo avatar --}}
                <div class="text-center mb-3">
                    @if($tenant->logo_url)
                        <img src="{{ $tenant->logo_url }}" alt="{{ $tenant->company_name }}"
                             class="rounded" style="max-height:80px;max-width:200px;object-fit:contain;border:1px solid #e2e8f0;padding:6px;background:#f8fafc;">
                    @else
                        <div class="d-inline-flex align-items-center justify-content-center rounded bg-primary text-white fw-bold"
                             style="width:72px;height:72px;font-size:1.6rem;">
                            {{ $tenant->initials }}
                        </div>
                    @endif
                </div>

                {{-- Logo upload form --}}
                <form action="{{ route('superadmin.clients.update', $tenant->id) }}" method="POST"
                      enctype="multipart/form-data" class="mb-3">
                    @csrf @method('PUT')
                    <label class="form-label small text-muted mb-1 d-block">
                        <i class="fas fa-image me-1"></i>{{ $tenant->logo_url ? 'Change logo' : 'Upload logo' }}
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="logo" class="form-control form-control-sm"
                               accept="image/png,image/jpeg,image/webp">
                        <button class="btn btn-outline-primary btn-sm" type="submit">Save</button>
                    </div>
                    <div class="form-text">PNG, JPG or WebP · max 2 MB</div>
                </form>

                <dl class="row mb-0" style="font-size:.9rem;">
                    <dt class="col-5 text-muted">ID</dt>
                    <dd class="col-7 fw-semibold">{{ $tenant->id }}</dd>

                    <dt class="col-5 text-muted">Company</dt>
                    <dd class="col-7">{{ $tenant->company_name ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7">{{ $tenant->email }}</dd>

                    <dt class="col-5 text-muted">Phone</dt>
                    <dd class="col-7">{{ $tenant->phone_number ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Package</dt>
                    <dd class="col-7"><span class="badge bg-primary">{{ $tenant->package->name ?? '—' }}</span></dd>

                    <dt class="col-5 text-muted">Plan</dt>
                    <dd class="col-7">{{ ucfirst(str_replace('_', ' ', $tenant->subscription_type ?? '—')) }}</dd>

                    <dt class="col-5 text-muted">Expiry</dt>
                    <dd class="col-7">
                        @if($tenant->expiry_date)
                            @php $exp = \Carbon\Carbon::parse($tenant->expiry_date); @endphp
                            <span class="{{ $exp->isPast() ? 'text-danger' : 'text-success' }}">
                                {{ $exp->format('d M Y') }}
                            </span>
                        @else —
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Status</dt>
                    <dd class="col-7">
                        @if($tenant->status)
                            <span class="badge badge-active">Active</span>
                        @else
                            <span class="badge badge-inactive">Inactive</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Joined</dt>
                    <dd class="col-7">{{ $tenant->created_at ? $tenant->created_at->format('d M Y') : '—' }}</dd>
                </dl>
            </div>
        </div>

        <!-- Subscription Management -->
        <div class="card mt-3">
            <div class="card-header py-3"><i class="fas fa-calendar-alt me-2"></i>Subscription</div>
            <div class="card-body">

                {{-- Add days --}}
                <p class="small text-muted mb-1 fw-semibold">Add days to current expiry</p>
                <form action="{{ route('superadmin.clients.extend-expiry', $tenant->id) }}" method="POST">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="number" name="days" class="form-control form-control-sm"
                               placeholder="e.g. 30" min="1" max="3650" required>
                        <button type="submit" class="btn btn-sm btn-success">+ Extend</button>
                    </div>
                </form>

                {{-- Set exact date + subscription type --}}
                <p class="small text-muted mb-1 fw-semibold">Set exact expiry &amp; billing period</p>
                <form action="{{ route('superadmin.clients.set-subscription', $tenant->id) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <select name="subscription_type" class="form-select form-select-sm" required>
                            <option value="free_trial" {{ $tenant->subscription_type=='free_trial'?'selected':'' }}>Free Trial</option>
                            <option value="monthly"    {{ $tenant->subscription_type=='monthly'?'selected':'' }}>Monthly</option>
                            <option value="yearly"     {{ $tenant->subscription_type=='yearly'?'selected':'' }}>Yearly</option>
                            <option value="one_time"   {{ $tenant->subscription_type=='one_time'?'selected':'' }}>One-Time / Lifetime</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="date" name="expiry_date" class="form-control form-control-sm"
                               value="{{ $tenant->expiry_date ? \Carbon\Carbon::parse($tenant->expiry_date)->format('Y-m-d') : '' }}"
                               placeholder="Leave blank to keep current">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                    <div class="form-text mt-1">Leave date blank to only change the billing period label.</div>
                </form>

            </div>
        </div>

        <!-- Send Welcome Email -->
        <div class="card mt-3">
            <div class="card-header py-3"><i class="fas fa-envelope me-2"></i>Welcome Email</div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Resend the registration welcome email to this tenant.
                    Since the original password is not stored, a password-reset link will be included instead.
                </p>
                <form action="{{ route('superadmin.clients.send-welcome', $tenant->id) }}" method="POST">
                    @csrf
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="attach_invoice" id="attach_invoice"
                               value="1" {{ $tenant->payments->isNotEmpty() ? '' : 'disabled' }}>
                        <label class="form-check-label" for="attach_invoice">
                            Attach latest invoice as PDF
                            @if($tenant->payments->isEmpty())
                                <span class="text-muted">(no payments on record)</span>
                            @endif
                        </label>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100"
                            onclick="return confirm('Send welcome email to {{ $tenant->email }}?')">
                        <i class="fas fa-paper-plane me-2"></i>Send Welcome Email
                    </button>
                </form>
            </div>
        </div>

        <!-- Toggle Status -->
        <div class="card mt-3">
            <div class="card-body">
                <form action="{{ route('superadmin.clients.toggle-status', $tenant->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn w-100 {{ $tenant->status ? 'btn-danger' : 'btn-success' }}">
                        <i class="fas {{ $tenant->status ? 'fa-ban' : 'fa-check-circle' }} me-2"></i>
                        {{ $tenant->status ? 'Suspend Client' : 'Activate Client' }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Support Plans (full-width below left column) -->
    <div class="col-12">
        <div class="card mt-1">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-headset me-2"></i>Support Plans</span>
                @php
                    $activeSupport = $tenant->supports->first(fn($s) => $s->status === 'active' && $s->ends_at->isFuture());
                @endphp
                @if($activeSupport)
                    <span class="badge bg-success">
                        Active — expires {{ $activeSupport->ends_at->format('d M Y') }}
                        ({{ $activeSupport->daysRemaining() }} days left)
                    </span>
                @else
                    <span class="badge bg-secondary">No Active Support</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Left: Add new support period --}}
                    <div class="col-lg-5">
                        <p class="small fw-semibold text-muted mb-2">Add Support Period</p>
                        <form action="{{ route('superadmin.clients.support.store', $tenant->id) }}" method="POST">
                            @csrf
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label form-label-sm">Start Date</label>
                                    <input type="date" name="starts_at" class="form-control form-control-sm"
                                           value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label form-label-sm">End Date</label>
                                    <input type="date" name="ends_at" class="form-control form-control-sm"
                                           value="{{ now()->addMonths(3)->toDateString() }}" required>
                                </div>
                                <div class="col-5">
                                    <label class="form-label form-label-sm">Amount</label>
                                    <input type="number" name="amount" step="0.01" min="0"
                                           class="form-control form-control-sm" placeholder="0.00">
                                </div>
                                <div class="col-3">
                                    <label class="form-label form-label-sm">Currency</label>
                                    <input type="text" name="currency" class="form-control form-control-sm"
                                           value="USD" maxlength="10">
                                </div>
                                <div class="col-4">
                                    <label class="form-label form-label-sm">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="active">Active</option>
                                        <option value="pending">Pending</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-label-sm">Payment Ref.</label>
                                    <input type="text" name="payment_ref" class="form-control form-control-sm"
                                           placeholder="Invoice / transaction reference">
                                </div>
                                <div class="col-12">
                                    <label class="form-label form-label-sm">Notes</label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2"
                                              placeholder="Optional internal notes"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-sm btn-success w-100">
                                        <i class="fas fa-plus me-1"></i>Add Support Period
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Right: Support history --}}
                    <div class="col-lg-7">
                        <p class="small fw-semibold text-muted mb-2">Support History</p>
                        @if($tenant->supports->isEmpty())
                            <p class="text-muted" style="font-size:.85rem;">No support periods recorded yet.</p>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>Period</th>
                                        <th>Amount</th>
                                        <th>Ref</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($tenant->supports as $support)
                                    @php
                                        $daysLeft = $support->daysRemaining();
                                        $sbadge   = match($support->status) {
                                            'active'    => $support->ends_at->isFuture() ? 'success' : 'warning',
                                            'pending'   => 'warning',
                                            'expired'   => 'secondary',
                                            'cancelled' => 'danger',
                                            default     => 'secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $support->starts_at->format('d M Y') }}
                                            <span class="text-muted">→</span>
                                            {{ $support->ends_at->format('d M Y') }}
                                            @if($support->status === 'active' && $support->ends_at->isFuture())
                                                <br><small class="{{ $daysLeft <= 30 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                                    {{ $daysLeft }} days left
                                                </small>
                                            @endif
                                            @if($support->notes)
                                                <br><small class="text-muted fst-italic">{{ $support->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($support->amount > 0)
                                                {{ $support->currency }} {{ number_format($support->amount, 2) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($support->payment_ref)
                                                <code style="font-size:.78rem;">{{ $support->payment_ref }}</code>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $sbadge }}">{{ ucfirst($support->status) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                {{-- Quick status toggle --}}
                                                @if($support->status !== 'active')
                                                <form action="{{ route('superadmin.clients.support.update', [$tenant->id, $support->id]) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="active">
                                                    <button class="btn btn-xs btn-outline-success" title="Set Active">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                @if($support->status !== 'cancelled')
                                                <form action="{{ route('superadmin.clients.support.update', [$tenant->id, $support->id]) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button class="btn btn-xs btn-outline-warning" title="Cancel">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                <form action="{{ route('superadmin.clients.support.destroy', [$tenant->id, $support->id]) }}" method="POST"
                                                      onsubmit="return confirm('Delete this support record?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-xs btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-credit-card me-2"></i>Payment History</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Ref</th>
                                <th>Plan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenant->payments as $payment)
                            <tr>
                                <td style="font-size:.85rem;">{{ $payment->created_at->format('d M Y') }}</td>
                                <td class="fw-semibold">{{ strtoupper($payment->currency ?? 'USD') }} {{ number_format($payment->amount, 2) }}</td>
                                <td><code style="font-size:.8rem;">{{ $payment->transaction_ref }}</code></td>
                                <td>{{ ucfirst($payment->subscription_type) }}</td>
                                <td>
                                    @php $sc = ['completed'=>'success','pending'=>'warning','failed'=>'danger','refunded'=>'secondary'][$payment->status] ?? 'secondary'; @endphp
                                    <span class="badge bg-{{ $sc }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-3 text-muted">No payments found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Contracts -->
        @php
            try {
                $clientContracts = \App\Models\landlord\TenantContract::where('tenant_id', $tenant->id)
                    ->latest()->get();
            } catch (\Throwable $e) { $clientContracts = collect(); }
        @endphp
        <div class="card mt-3">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-file-contract me-2"></i>Contracts & SLAs</span>
                <a href="{{ route('superadmin.contracts.create', ['tenant_id' => $tenant->id]) }}"
                   class="btn btn-xs btn-outline-primary">+ New</a>
            </div>
            <div class="card-body p-0">
                @if($clientContracts->isEmpty())
                    <p class="text-muted p-3 mb-0" style="font-size:.85rem;">No contracts yet.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                        <thead class="table-light">
                            <tr><th>Type</th><th>Date</th><th>Amount</th><th>PDF</th><th>Sent</th></tr>
                        </thead>
                        <tbody>
                        @foreach($clientContracts as $c)
                            <tr>
                                <td>
                                    @php $b = match($c->document_type){ 'full_contract'=>'primary','sla_only'=>'info','sow_only'=>'warning',default=>'secondary' }; @endphp
                                    <a href="{{ route('superadmin.contracts.show', $c->id) }}" class="text-decoration-none">
                                        <span class="badge bg-{{ $b }}">{{ $c->getDocumentTypeLabel() }}</span>
                                    </a>
                                </td>
                                <td>{{ $c->effective_date?->format('d M Y') }}</td>
                                <td>{{ $c->contract_amount ? '$'.number_format($c->contract_amount,0) : '—' }}</td>
                                <td>
                                    @if($c->pdf_path)
                                        <a href="{{ route('superadmin.contracts.download', $c->id) }}" target="_blank" class="text-danger">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($c->sent_at)
                                        <i class="fas fa-check-circle text-success" title="Sent {{ $c->sent_at->format('d M Y') }}"></i>
                                    @else
                                        <i class="fas fa-clock text-muted" title="Not sent"></i>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <!-- Domain Info -->
        <div class="card mt-3">
            <div class="card-header py-3"><i class="fas fa-globe me-2"></i>Domains</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Domain</th><th>Type</th></tr>
                        </thead>
                        <tbody>
                            @php $domain = $tenant->id . '.' . env('CENTRAL_DOMAIN', 'novaxpos.com'); @endphp
                            <tr>
                                <td><a href="http://{{ $domain }}" target="_blank">{{ $domain }}</a></td>
                                <td><span class="badge bg-info">Subdomain</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
