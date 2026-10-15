<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice {{ $invoice->invoice_number }}</title>
@php
    try {
        $theme = \App\Models\landlord\SaasSettings::allCached();
    } catch (\Throwable $e) {
        $theme = [];
    }
    $siteName = $theme['site_name'] ?? 'NovaxPOS';
    $siteFont = $theme['site_font'] ?? 'Inter';
    $fontSlug = str_replace(' ', '+', $siteFont);
    $tp       = $theme['theme_primary'] ?? '#0066CC';
    $td       = $theme['theme_dark']    ?? '#003366';
    $ta       = $theme['theme_accent']  ?? '#66FF33';
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family={{ $fontSlug }}:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('fontawesome/css/all.min.css') }}">
<style>
/* ── Reset ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: '{{ $siteFont }}', 'Inter', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #f5f5f5; }

/* ── Print Bar (screen only) ── */
.print-bar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 999;
    background: {{ $td }}; color: #fff; padding: .75rem 2rem;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
}
.print-bar .pb-title { font-weight: 700; font-size: .9rem; }
.print-bar .pb-actions { display: flex; gap: .75rem; }
.btn-print {
    background: {{ $ta }}; color: #000; border: none; padding: .5rem 1.25rem;
    border-radius: 6px; font-weight: 700; cursor: pointer; font-size: .85rem; text-decoration: none;
}
.btn-back {
    background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3);
    padding: .5rem 1.25rem; border-radius: 6px; cursor: pointer; font-size: .85rem;
    text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
}
.btn-back:hover { background: rgba(255,255,255,.25); color: #fff; }

/* ── Page wrapper ── */
.page-shell { padding: 70px 2rem 2rem; }

/* ── Invoice paper ── */
.invoice-paper {
    max-width: 800px; margin: 0 auto; background: #fff;
    border-radius: 4px; box-shadow: 0 2px 20px rgba(0,0,0,.12); overflow: hidden;
}

/* ── Header ── */
.inv-header {
    background: linear-gradient(135deg, {{ $td }}, {{ $tp }});
    color: #fff; padding: 2.5rem 2.5rem 2rem;
}
.inv-header-top { display: flex; justify-content: space-between; align-items: flex-start; }
.brand-name { font-size: 1.6rem; font-weight: 800; letter-spacing: -.03em; }
.brand-name span { color: {{ $ta }}; }
.brand-sub { font-size: .7rem; opacity: .5; margin-top: 2px; letter-spacing: .06em; text-transform: uppercase; }
.inv-number-block { text-align: right; }
.inv-label { font-size: .65rem; opacity: .6; text-transform: uppercase; letter-spacing: .1em; }
.inv-number { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; }
.inv-status {
    display: inline-block; padding: 3px 12px; border-radius: 20px;
    font-size: .7rem; font-weight: 700; text-transform: uppercase; margin-top: 6px;
    letter-spacing: .06em;
}
.status-unpaid    { background: #fee2e2; color: #b91c1c; }
.status-paid      { background: #dcfce7; color: #166534; }
.status-partial   { background: #fef3c7; color: #92400e; }
.status-cancelled { background: #f1f5f9; color: #475569; }
.overdue-tag { background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 4px; font-size: .65rem; font-weight: 700; margin-left: 6px; }

.inv-header-meta {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;
    margin-top: 1.75rem; padding-top: 1.25rem;
    border-top: 1px solid rgba(255,255,255,.15);
}
.meta-item .meta-label { font-size: .62rem; opacity: .55; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 3px; }
.meta-item .meta-val   { font-weight: 600; font-size: .88rem; }

/* ── Body sections ── */
.inv-body { padding: 0 2.5rem; }

.bill-section {
    display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;
    padding: 1.75rem 0; border-bottom: 1px solid #e2e8f0;
}
.bill-block .section-label {
    font-size: .62rem; text-transform: uppercase; letter-spacing: .1em;
    color: #94a3b8; font-weight: 700; margin-bottom: .6rem;
}
.bill-block .company  { font-size: 1rem; font-weight: 700; color: #0f172a; }
.bill-block .contact  { font-size: .82rem; color: #475569; margin-top: 2px; }
.bill-block .email    { font-size: .82rem; color: {{ $tp }}; margin-top: 2px; }
.bill-block .from-val { font-size: .88rem; color: #374151; }

/* ── Line items table ── */
.items-section { padding: 1.75rem 0; border-bottom: 1px solid #e2e8f0; }
.items-section h4 { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; margin-bottom: 1rem; }
.items-table { width: 100%; border-collapse: collapse; }
.items-table thead tr { background: #f8fafc; }
.items-table th { padding: .6rem .75rem; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #64748b; text-align: left; border-bottom: 2px solid #e2e8f0; }
.items-table th.right, .items-table td.right { text-align: right; }
.items-table td { padding: .65rem .75rem; font-size: .85rem; border-bottom: 1px solid #f1f5f9; color: #374151; }
.items-table tbody tr:last-child td { border-bottom: none; }
.items-table tfoot tr { background: #f8fafc; }
.items-table tfoot td { padding: .5rem .75rem; font-size: .85rem; }
.items-table tfoot .total-row td { font-weight: 700; font-size: .95rem; background: {{ $td }}; color: #fff; }
.items-table tfoot .balance-row td { font-weight: 700; font-size: .95rem; background: #f0fdf4; color: #166534; }

/* ── Payment & Notes ── */
.inv-footer-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;
    padding: 1.75rem 0;
}
.section-label-sm { font-size: .62rem; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; font-weight: 700; margin-bottom: .5rem; }
.payment-info-row { display: flex; justify-content: space-between; font-size: .82rem; padding: .25rem 0; border-bottom: 1px solid #f1f5f9; }
.payment-info-row:last-child { border-bottom: none; }
.payment-label { color: #64748b; }
.payment-val   { font-weight: 600; }
.notes-text { font-size: .82rem; color: #374151; line-height: 1.6; white-space: pre-line; background: #f8fafc; border-radius: 8px; padding: .75rem 1rem; }

/* ── Footer bar ── */
.inv-footer-bar {
    background: {{ $td }}; color: rgba(255,255,255,.5);
    padding: .9rem 2.5rem; display: flex; justify-content: space-between; align-items: center;
    font-size: .7rem;
}
.inv-footer-bar .footer-brand { color: rgba(255,255,255,.8); font-weight: 600; }
.inv-footer-bar .footer-ref   { font-family: monospace; font-size: .68rem; }

/* ── PRINT OVERRIDES ── */
@media print {
    body { background: #fff !important; }
    .print-bar { display: none !important; }
    .page-shell { padding: 0 !important; }
    .invoice-paper { box-shadow: none !important; border-radius: 0 !important; max-width: 100% !important; }
    @page { size: A4; margin: 10mm 12mm 15mm; }
}
</style>
</head>
<body>

<!-- Print Bar (screen only) -->
<div class="print-bar">
    <div class="pb-title"><i class="fas fa-file-invoice me-2"></i>{{ $invoice->invoice_number }} — {{ $invoice->tenant_company ?: $invoice->tenant_name }}</div>
    <div class="pb-actions">
        <a href="{{ route('superadmin.invoices.show', $invoice) }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print me-1"></i> Print / Save PDF
        </button>
    </div>
</div>

<div class="page-shell">
<div class="invoice-paper">

    <!-- Header -->
    <div class="inv-header">
        <div class="inv-header-top">
            <div>
                <div class="brand-name">{{ $siteName }}<span>.</span></div>
                <div class="brand-sub">SaaS Platform — Official Invoice</div>
            </div>
            <div class="inv-number-block">
                <div class="inv-label">Invoice</div>
                <div class="inv-number">{{ $invoice->invoice_number }}</div>
                <div style="margin-top:6px;">
                    <span class="inv-status status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                    @if($invoice->isOverdue())<span class="overdue-tag">OVERDUE</span>@endif
                </div>
            </div>
        </div>
        <div class="inv-header-meta">
            <div class="meta-item">
                <div class="meta-label">Issue Date</div>
                <div class="meta-val">{{ $invoice->invoice_date->format('d M Y') }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Due Date</div>
                <div class="meta-val">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Currency</div>
                <div class="meta-val">{{ $invoice->currency }}</div>
            </div>
        </div>
    </div>

    <!-- Bill To / From -->
    <div class="inv-body">
        <div class="bill-section">
            <div class="bill-block">
                <div class="section-label">Bill To</div>
                <div class="company">{{ $invoice->tenant_company ?: $invoice->tenant_name }}</div>
                @if($invoice->tenant_company)
                <div class="contact">{{ $invoice->tenant_name }}</div>
                @endif
                @if($invoice->tenant_email)
                <div class="email">{{ $invoice->tenant_email }}</div>
                @endif
            </div>
            <div class="bill-block" style="text-align:right;">
                <div class="section-label">From</div>
                <div class="company">{{ $siteName }}</div>
                <div class="contact from-val">SaaS Platform Administrator</div>
                @if(!empty($theme['site_email']))
                <div class="email">{{ $theme['site_email'] }}</div>
                @endif
                @if($invoice->reference)
                <div class="contact" style="margin-top:.5rem;">Ref: <strong>{{ $invoice->reference }}</strong></div>
                @endif
            </div>
        </div>

        <!-- Line Items -->
        <div class="items-section">
            <h4>Items &amp; Services</h4>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:45%">Description</th>
                        <th style="width:12%" class="right">Qty</th>
                        <th style="width:18%" class="right">Unit Price</th>
                        <th style="width:18%" class="right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->line_items ?? [] as $item)
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td class="right">{{ number_format($item['qty'], 2) }}</td>
                        <td class="right">{{ $invoice->currency }} {{ number_format($item['unit_price'], 2) }}</td>
                        <td class="right" style="font-weight:600;">{{ $invoice->currency }} {{ number_format($item['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="right" style="color:#64748b;">Subtotal</td>
                        <td class="right" style="font-weight:600;">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->tax_rate > 0)
                    <tr>
                        <td colspan="3" class="right" style="color:#64748b;">Tax ({{ $invoice->tax_rate }}%)</td>
                        <td class="right">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($invoice->discount > 0)
                    <tr>
                        <td colspan="3" class="right" style="color:#64748b;">Discount</td>
                        <td class="right" style="color:#dc2626;">-{{ $invoice->currency }} {{ number_format($invoice->discount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td colspan="3" class="right">Total Due</td>
                        <td class="right">{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</td>
                    </tr>
                    @if($invoice->amount_paid > 0)
                    <tr>
                        <td colspan="3" class="right" style="color:#64748b;">Amount Paid</td>
                        <td class="right" style="color:#16a34a;font-weight:600;">-{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</td>
                    </tr>
                    <tr class="balance-row">
                        <td colspan="3" class="right">Balance Due</td>
                        <td class="right">{{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}</td>
                    </tr>
                    @endif
                </tfoot>
            </table>
        </div>

        <!-- Payment Info + Notes -->
        <div class="inv-footer-grid">
            <div>
                <div class="section-label-sm">Payment Info</div>
                @if($invoice->payment_method)
                <div class="payment-info-row">
                    <span class="payment-label">Method</span>
                    <span class="payment-val">{{ ucfirst(str_replace('_', ' ', $invoice->payment_method)) }}</span>
                </div>
                @endif
                @if($invoice->paid_date)
                <div class="payment-info-row">
                    <span class="payment-label">Paid On</span>
                    <span class="payment-val">{{ \Carbon\Carbon::parse($invoice->paid_date)->format('d M Y') }}</span>
                </div>
                @endif
                <div class="payment-info-row">
                    <span class="payment-label">Amount Paid</span>
                    <span class="payment-val" style="color:#16a34a;">{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                <div class="payment-info-row" style="border-top:1px solid #e2e8f0;margin-top:.25rem;padding-top:.35rem;">
                    <span class="payment-label" style="font-weight:700;">Balance Due</span>
                    <span class="payment-val" style="color:{{ $invoice->balance_due > 0 ? '#dc2626' : '#16a34a' }};font-size:.95rem;">
                        {{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}
                    </span>
                </div>
            </div>

            @if($invoice->notes)
            <div>
                <div class="section-label-sm">Notes</div>
                <div class="notes-text">{{ $invoice->notes }}</div>
            </div>
            @else
            <div>
                <div class="section-label-sm">Thank You</div>
                <div class="notes-text" style="color:#64748b;">Thank you for your business. For queries regarding this invoice, please contact your platform administrator.</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Footer bar -->
    <div class="inv-footer-bar">
        <div class="footer-brand">{{ $siteName }} — SaaS Platform</div>
        <div class="footer-ref">{{ $invoice->invoice_number }} &nbsp;|&nbsp; Generated {{ now()->format('d M Y, H:i') }}</div>
    </div>

</div>
</div>

</body>
</html>
