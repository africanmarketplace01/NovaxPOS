@extends('superadmin.layout.main')
@section('title', 'Invoices')
@section('page-title', 'Invoices')

@section('styles')
<style>
.inv-stat { border-radius:12px;padding:1.25rem 1.5rem;color:#fff;display:flex;align-items:center;justify-content:space-between; }
.inv-stat .stat-val { font-size:1.6rem;font-weight:800;line-height:1; }
.inv-stat .stat-lbl { font-size:.78rem;opacity:.8;margin-top:3px; }
.inv-stat i { font-size:2rem;opacity:.3; }
.status-unpaid   { background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600; }
.status-paid     { background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600; }
.status-partial  { background:#fffbeb;color:#d97706;border:1px solid #fde68a;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600; }
.status-cancelled{ background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600; }
.overdue-tag     { background:#fee2e2;color:#b91c1c;padding:2px 6px;border-radius:4px;font-size:.68rem;font-weight:700;margin-left:4px; }
</style>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="inv-stat" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
            <div><div class="stat-val">{{ $stats['total'] }}</div><div class="stat-lbl">Total Invoices</div></div>
            <i class="fas fa-file-invoice"></i>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="inv-stat" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
            <div><div class="stat-val">{{ $stats['unpaid'] }}</div><div class="stat-lbl">Unpaid</div></div>
            <i class="fas fa-clock"></i>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="inv-stat" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
            <div><div class="stat-val">{{ $stats['overdue'] }}</div><div class="stat-lbl">Overdue</div></div>
            <i class="fas fa-exclamation-triangle"></i>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="inv-stat" style="background:linear-gradient(135deg,#10b981,#059669)">
            <div><div class="stat-val">{{ $stats['paid'] }}</div><div class="stat-lbl">Paid</div></div>
            <i class="fas fa-check-circle"></i>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="inv-stat" style="background:linear-gradient(135deg,#0ea5e9,#0284c7)">
            <div><div class="stat-val">${{ number_format($stats['revenue'], 0) }}</div><div class="stat-lbl">Total Revenue</div></div>
            <i class="fas fa-dollar-sign"></i>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="inv-stat" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)">
            <div><div class="stat-val">${{ number_format($stats['outstanding'], 0) }}</div><div class="stat-lbl">Outstanding</div></div>
            <i class="fas fa-hourglass-half"></i>
        </div>
    </div>
</div>

{{-- Filters + Actions --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control form-control-sm" placeholder="Search invoice #, name, company, email…">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="unpaid"    {{ request('status')=='unpaid'    ?'selected':'' }}>Unpaid</option>
                    <option value="paid"      {{ request('status')=='paid'      ?'selected':'' }}>Paid</option>
                    <option value="partial"   {{ request('status')=='partial'   ?'selected':'' }}>Partial</option>
                    <option value="cancelled" {{ request('status')=='cancelled' ?'selected':'' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                <a href="{{ route('superadmin.invoices.index') }}" class="btn btn-sm btn-light ms-1">Clear</a>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('superadmin.invoices.create') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>New Invoice
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('superadmin.invoices.show', $inv) }}" class="fw-semibold text-decoration-none">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size:.88rem;">{{ $inv->tenant_company ?: $inv->tenant_name }}</div>
                            <small class="text-muted">{{ $inv->tenant_email }}</small>
                        </td>
                        <td style="font-size:.85rem;">{{ $inv->invoice_date->format('d M Y') }}</td>
                        <td style="font-size:.85rem;">
                            @if($inv->due_date)
                                {{ $inv->due_date->format('d M Y') }}
                                @if($inv->isOverdue()) <span class="overdue-tag">OVERDUE</span> @endif
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="text-end fw-semibold">{{ $inv->currency }} {{ number_format($inv->total, 2) }}</td>
                        <td class="text-end text-success">{{ $inv->currency }} {{ number_format($inv->amount_paid, 2) }}</td>
                        <td class="text-end {{ $inv->balance_due > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $inv->currency }} {{ number_format($inv->balance_due, 2) }}
                        </td>
                        <td>
                            <span class="status-{{ $inv->status }}">{{ ucfirst($inv->status) }}</span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="{{ route('superadmin.invoices.show', $inv) }}" class="btn btn-xs btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('superadmin.invoices.print', $inv) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Print PDF">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="{{ route('superadmin.invoices.edit', $inv) }}" class="btn btn-xs btn-outline-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('superadmin.invoices.destroy', $inv) }}" method="POST" onsubmit="return confirm('Delete this invoice?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-file-invoice fa-2x mb-2 d-block opacity-25"></i>
                            No invoices found.
                            <a href="{{ route('superadmin.invoices.create') }}">Create one now →</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer py-3">
        {{ $invoices->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
