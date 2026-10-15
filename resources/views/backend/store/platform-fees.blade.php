@extends('backend.layout.main')
@section('title', 'Platform Fees')

@section('content')
<section class="forms">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem;">
            <div>
                <h5 class="mb-0" style="font-weight:600;">Marketplace Platform Fees</h5>
                <small class="text-muted">Invoices issued by the platform for your marketplace sales.</small>
            </div>
            <a href="{{ route('store.marketplace.listings') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left" style="margin-right:4px;"></i>Back to Listings
            </a>
        </div>

        <x-success-message key="message" />

        @include('backend.store.partials.marketplace-hold-banner')

        <h6 style="font-weight:600;margin:0 0 .5rem;">Fee invoices <small class="text-muted" style="font-weight:400;">— for cash-on-delivery orders, where you collected the money</small></h6>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr><th>Invoice Ref</th><th>Period</th><th class="text-center">Orders</th><th class="text-end">Your Sales</th><th class="text-end">Fee Payable</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($feeRequests as $fr)
                        <tr>
                            <td style="font-weight:600;">{{ $fr->reference }}</td>
                            <td><small>{{ \Carbon\Carbon::parse($fr->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($fr->period_end)->format('d M Y') }}</small></td>
                            <td class="text-center">{{ $fr->orders_count }}</td>
                            <td class="text-end">{{ $fr->currency }} {{ number_format($fr->sales_subtotal, 2) }}</td>
                            <td class="text-end" style="font-weight:700;color:{{ $fr->status === 'paid' ? '#16a34a' : '#dc2626' }};">
                                {{ $fr->currency }} {{ number_format($fr->fee_amount, 2) }}
                            </td>
                            <td>
                                @php $badge = ['sent' => 'warning', 'paid' => 'success', 'cancelled' => 'secondary'][$fr->status] ?? 'secondary'; @endphp
                                <span class="badge badge-{{ $badge }}">{{ $fr->status === 'sent' ? 'Payable' : ucfirst($fr->status) }}</span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-link" type="button"
                                        onclick="document.getElementById('fr-lines-{{ $fr->id }}').classList.toggle('d-none');">
                                    View breakdown
                                </button>
                            </td>
                        </tr>
                        <tr id="fr-lines-{{ $fr->id }}" class="d-none">
                            <td colspan="7" style="background:#f8f9fc;">
                                @php $lines = json_decode($fr->breakdown ?? '[]', true) ?: []; @endphp
                                <table class="table table-sm mb-0" style="font-size:.82rem;">
                                    <thead><tr><th>Order</th><th>Date</th><th>Payment</th><th class="text-end">Your Sales</th><th class="text-end">Fee</th></tr></thead>
                                    <tbody>
                                        @foreach($lines as $line)
                                        <tr>
                                            <td>{{ $line['order_number'] ?? '—' }}</td>
                                            <td>{{ $line['date'] ?? '' }}</td>
                                            <td style="text-transform:uppercase;">{{ $line['payment_method'] ?? '' }}</td>
                                            <td class="text-end">{{ number_format($line['subtotal'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($line['fee_share'] ?? 0, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No platform fee invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <h6 style="font-weight:600;margin:1.5rem 0 .5rem;">Payouts <small class="text-muted" style="font-weight:400;">— for online-paid orders, where the platform collected the money</small></h6>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr><th>Payout Ref</th><th>Period</th><th class="text-center">Orders</th><th class="text-end">Your Sales</th><th class="text-end">Commission</th><th class="text-end">Amount</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $po)
                        <tr>
                            <td style="font-weight:600;">{{ $po->reference }}</td>
                            <td><small>{{ \Carbon\Carbon::parse($po->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($po->period_end)->format('d M Y') }}</small></td>
                            <td class="text-center">{{ $po->orders_count }}</td>
                            <td class="text-end">{{ $po->currency }} {{ number_format($po->gross_amount, 2) }}</td>
                            <td class="text-end text-muted">{{ (float) $po->commission_amount > 0 ? '−' . number_format($po->commission_amount, 2) : '—' }}</td>
                            <td class="text-end" style="font-weight:700;color:#16a34a;">{{ $po->currency }} {{ number_format($po->net_amount, 2) }}</td>
                            <td>
                                @if ($po->status === 'paid')
                                    <span class="badge badge-success">Sent</span>
                                    <div><small class="text-muted">{{ strtoupper($po->payout_method) }}{{ $po->payout_reference ? ' · ' . $po->payout_reference : '' }}{{ $po->paid_at ? ' · ' . \Carbon\Carbon::parse($po->paid_at)->format('d M') : '' }}</small></div>
                                @else
                                    <span class="badge badge-warning">Processing</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-link" type="button" onclick="document.getElementById('po-lines-{{ $po->id }}').classList.toggle('d-none');">View breakdown</button>
                            </td>
                        </tr>
                        <tr id="po-lines-{{ $po->id }}" class="d-none">
                            <td colspan="8" style="background:#f8f9fc;">
                                @php $poLines = json_decode($po->breakdown ?? '[]', true) ?: []; @endphp
                                <table class="table table-sm mb-0" style="font-size:.82rem;">
                                    <thead><tr><th>Order</th><th>Date</th><th>Payment</th><th class="text-end">Your Sales</th><th class="text-end">Commission</th><th class="text-end">Net</th></tr></thead>
                                    <tbody>
                                        @foreach($poLines as $line)
                                        <tr>
                                            <td>{{ $line['order_number'] ?? '—' }}</td>
                                            <td>{{ $line['date'] ?? '' }}</td>
                                            <td style="text-transform:uppercase;">{{ $line['payment_method'] ?? '' }}</td>
                                            <td class="text-end">{{ number_format($line['gross'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($line['commission'] ?? 0, 2) }}</td>
                                            <td class="text-end">{{ number_format($line['net'] ?? 0, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">No payouts yet. Online-paid marketplace orders are paid out here.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
@endsection
