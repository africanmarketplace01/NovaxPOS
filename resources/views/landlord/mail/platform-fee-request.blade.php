<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;margin:0;padding:24px;">
@php $lines = json_decode($feeRequest->breakdown ?? '[]', true) ?: []; @endphp
<div style="max-width:640px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
    <div style="background:#0f172a;color:#fff;padding:18px 24px;">
        <h2 style="margin:0;font-size:18px;">{{ $senderName }} — Platform Fee Invoice</h2>
    </div>
    <div style="padding:24px;">
        <p style="margin-top:0;">Hello {{ $feeRequest->tenant_name }},</p>
        <p>Please find below the platform fees payable for marketplace orders processed through your store.</p>

        <table style="width:100%;border-collapse:collapse;font-size:14px;margin:14px 0;">
            <tr><td style="padding:5px 0;color:#6b7280;">Invoice Ref.</td><td style="text-align:right;font-weight:bold;">{{ $feeRequest->reference }}</td></tr>
            <tr><td style="padding:5px 0;color:#6b7280;">Billing Period</td><td style="text-align:right;">{{ \Carbon\Carbon::parse($feeRequest->period_start)->format('d M Y') }} — {{ \Carbon\Carbon::parse($feeRequest->period_end)->format('d M Y') }}</td></tr>
            <tr><td style="padding:5px 0;color:#6b7280;">Marketplace Orders</td><td style="text-align:right;">{{ $feeRequest->orders_count }}</td></tr>
            <tr><td style="padding:5px 0;color:#6b7280;">Your Sales (subtotal)</td><td style="text-align:right;">{{ $feeRequest->currency }} {{ number_format((float) $feeRequest->sales_subtotal, 2) }}</td></tr>
            <tr><td style="padding:8px 0;font-weight:bold;font-size:16px;">Platform Fee Payable</td><td style="text-align:right;font-weight:bold;font-size:16px;color:#dc2626;">{{ $feeRequest->currency }} {{ number_format((float) $feeRequest->fee_amount, 2) }}</td></tr>
        </table>

        @if(count($lines))
        <h4 style="margin:18px 0 8px;font-size:14px;">Order Breakdown</h4>
        <table style="width:100%;border-collapse:collapse;font-size:12px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:7px;border-bottom:1px solid #e5e7eb;">Order</th>
                    <th style="text-align:left;padding:7px;border-bottom:1px solid #e5e7eb;">Date</th>
                    <th style="text-align:left;padding:7px;border-bottom:1px solid #e5e7eb;">Payment</th>
                    <th style="text-align:right;padding:7px;border-bottom:1px solid #e5e7eb;">Your Sales</th>
                    <th style="text-align:right;padding:7px;border-bottom:1px solid #e5e7eb;">Fee</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr>
                    <td style="padding:6px 7px;border-bottom:1px solid #f1f5f9;">{{ $line['order_number'] ?? '—' }}</td>
                    <td style="padding:6px 7px;border-bottom:1px solid #f1f5f9;">{{ $line['date'] ?? '' }}</td>
                    <td style="padding:6px 7px;border-bottom:1px solid #f1f5f9;text-transform:uppercase;">{{ $line['payment_method'] ?? '' }}</td>
                    <td style="padding:6px 7px;border-bottom:1px solid #f1f5f9;text-align:right;">{{ number_format($line['subtotal'] ?? 0, 2) }}</td>
                    <td style="padding:6px 7px;border-bottom:1px solid #f1f5f9;text-align:right;">{{ number_format($line['fee_share'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if($feeRequest->note)
        <p style="background:#fffbeb;border-left:3px solid #f59e0b;padding:10px 12px;font-size:13px;">{{ $feeRequest->note }}</p>
        @endif

        <p style="font-size:13px;color:#6b7280;">Please settle this invoice using your usual payment channel. Contact platform support with the invoice reference for any questions.</p>
    </div>
    <div style="background:#f8fafc;padding:14px 24px;font-size:12px;color:#94a3b8;">
        This is an automated invoice from {{ $senderName }}.
    </div>
</div>
</body>
</html>
