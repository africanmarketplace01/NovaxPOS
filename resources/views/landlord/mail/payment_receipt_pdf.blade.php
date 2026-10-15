<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Receipt</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; }
  .page { padding: 40px 48px; }

  /* Header */
  .header { display: table; width: 100%; margin-bottom: 32px; }
  .header-left  { display: table-cell; vertical-align: middle; }
  .header-right { display: table-cell; vertical-align: middle; text-align: right; }
  .brand { font-size: 22px; font-weight: 700; color: #0f172a; letter-spacing: -.5px; }
  .brand span { color: #0066CC; }
  .site-email { font-size: 11px; color: #64748b; margin-top: 3px; }
  .badge-receipt { display: inline-block; background: #0066CC; color: #fff;
                   font-size: 11px; font-weight: 700; letter-spacing: .06em;
                   padding: 4px 12px; border-radius: 20px; text-transform: uppercase; }

  /* Divider */
  .divider { border: none; border-top: 2px solid #e2e8f0; margin: 20px 0; }

  /* Receipt meta */
  .meta-table { width: 100%; margin-bottom: 28px; }
  .meta-table td { padding: 4px 0; font-size: 12px; vertical-align: top; }
  .meta-table .label { color: #64748b; width: 160px; }
  .meta-table .value { font-weight: 600; }

  /* To / From */
  .parties { display: table; width: 100%; margin-bottom: 28px; }
  .party { display: table-cell; width: 50%; vertical-align: top; }
  .party-label { font-size: 10px; text-transform: uppercase; letter-spacing: .07em;
                 color: #94a3b8; margin-bottom: 6px; font-weight: 700; }
  .party-name  { font-size: 14px; font-weight: 700; color: #0f172a; }
  .party-detail { font-size: 12px; color: #64748b; margin-top: 2px; }

  /* Items table */
  .items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  .items thead tr { background: #0f172a; color: #fff; }
  .items thead th { padding: 9px 12px; text-align: left; font-size: 11px;
                    text-transform: uppercase; letter-spacing: .06em; }
  .items tbody tr:nth-child(even) { background: #f8fafc; }
  .items tbody td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #e2e8f0; }
  .items tfoot td { padding: 10px 12px; }
  .total-row { font-size: 15px; font-weight: 700; }
  .total-label { text-align: right; color: #64748b; }
  .total-value { color: #0f172a; }

  /* Status badge */
  .status-completed { color: #166534; background: #dcfce7; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
  .status-pending   { color: #92400e; background: #fef9c3; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
  .status-failed    { color: #991b1b; background: #fee2e2; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

  /* Notice */
  .notice { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;
            padding: 14px 16px; font-size: 11px; color: #64748b; margin-top: 28px; line-height: 1.6; }

  .footer { margin-top: 36px; text-align: center; font-size: 11px; color: #94a3b8; }
</style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="header">
    <div class="header-left">
      <div class="brand">{{ $site_name }}</div>
      <div class="site-email">{{ $site_email }}</div>
    </div>
    <div class="header-right">
      <div class="badge-receipt">Payment Receipt</div>
    </div>
  </div>

  <hr class="divider">

  <!-- Receipt Meta -->
  <table class="meta-table">
    <tr>
      <td class="label">Receipt No.</td>
      <td class="value">#{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
    </tr>
    <tr>
      <td class="label">Transaction Ref.</td>
      <td class="value">{{ $payment->transaction_ref ?? '—' }}</td>
    </tr>
    <tr>
      <td class="label">Date</td>
      <td class="value">{{ $payment->created_at ? $payment->created_at->format('d M Y, H:i') : '—' }}</td>
    </tr>
    <tr>
      <td class="label">Payment Method</td>
      <td class="value">{{ ucfirst($payment->paid_by ?? 'N/A') }}</td>
    </tr>
    <tr>
      <td class="label">Status</td>
      <td class="value">
        @php $s = $payment->status ?? 'pending'; @endphp
        <span class="status-{{ $s }}">{{ ucfirst($s) }}</span>
      </td>
    </tr>
  </table>

  <!-- Parties -->
  <div class="parties">
    <div class="party">
      <div class="party-label">Bill To</div>
      <div class="party-name">{{ $tenant->company_name ?? $tenant->id }}</div>
      <div class="party-detail">{{ $tenant->email }}</div>
      @if($tenant->phone_number)
      <div class="party-detail">{{ $tenant->phone_number }}</div>
      @endif
      <div class="party-detail">
        <a href="{{ $login_url }}" style="color:#0066CC;">{{ $tenant->id }}.{{ env('CENTRAL_DOMAIN') }}</a>
      </div>
    </div>
    <div class="party">
      <div class="party-label">Issued By</div>
      <div class="party-name">{{ $site_name }}</div>
      <div class="party-detail">{{ $site_email }}</div>
    </div>
  </div>

  <hr class="divider">

  <!-- Line Items -->
  <table class="items">
    <thead>
      <tr>
        <th style="width:50%;">Description</th>
        <th>Plan</th>
        <th>Period</th>
        <th style="text-align:right;">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          {{ $site_name }} Subscription
          @if($payment->package)
            — {{ $payment->package->name }}
          @endif
        </td>
        <td>{{ ucfirst(str_replace('_', ' ', $payment->subscription_type ?? '—')) }}</td>
        <td>{{ $payment->created_at ? $payment->created_at->format('M Y') : '—' }}</td>
        <td style="text-align:right;">
          {{ strtoupper($payment->currency ?? 'USD') }}
          {{ number_format($payment->amount ?? 0, 2) }}
        </td>
      </tr>
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="3" class="total-label">Total Paid</td>
        <td style="text-align:right;" class="total-value">
          {{ strtoupper($payment->currency ?? 'USD') }}
          {{ number_format($payment->amount ?? 0, 2) }}
        </td>
      </tr>
    </tfoot>
  </table>

  <!-- Notice -->
  <div class="notice">
    This receipt confirms your payment for a subscription to <strong>{{ $site_name }}</strong>.
    Please keep this document for your records. If you have any questions, contact us at
    <strong>{{ $site_email }}</strong>.
  </div>

  <div class="footer">
    &copy; {{ date('Y') }} {{ $site_name }}. All rights reserved. &bull;
    Generated {{ now()->format('d M Y H:i') }}
  </div>

</div>
</body>
</html>
