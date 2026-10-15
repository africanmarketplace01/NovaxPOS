<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;margin:0;padding:24px;">
<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
    <div style="background:#0f172a;color:#fff;padding:18px 24px;">
        <h2 style="margin:0;font-size:18px;">{{ $senderName }} — Marketplace</h2>
    </div>
    <div style="padding:24px;">
        @if($recipientRole === 'vendor')
            <p style="margin-top:0;">Hello {{ $recipientName ?? 'Vendor' }},</p>
            <p>You have received a <strong>new order</strong> from the global marketplace. It is also available in your admin panel under <strong>Online Store → Orders</strong>.</p>
        @elseif($recipientRole === 'agency')
            <p style="margin-top:0;">Hello {{ $recipientName ?? 'Partner' }},</p>
            <p>A new marketplace order has been assigned to your agency for <strong>delivery</strong>. Details below.</p>
        @else
            <p style="margin-top:0;">Hello Admin,</p>
            <p>A new order was placed on the global marketplace.</p>
        @endif

        <table style="width:100%;border-collapse:collapse;font-size:14px;margin:14px 0;">
            <tr><td style="padding:6px 0;color:#6b7280;">Order No.</td><td style="text-align:right;font-weight:bold;">{{ $order->order_number }}</td></tr>
            <tr><td style="padding:6px 0;color:#6b7280;">Date</td><td style="text-align:right;">{{ $order->created_at->format('d M Y H:i') }}</td></tr>
            <tr><td style="padding:6px 0;color:#6b7280;">Customer</td><td style="text-align:right;">{{ $order->customer_name }} ({{ $order->customer_phone }})</td></tr>
            <tr><td style="padding:6px 0;color:#6b7280;">Delivery Address</td><td style="text-align:right;">{{ $order->customer_address }}</td></tr>
            <tr><td style="padding:6px 0;color:#6b7280;">Payment</td><td style="text-align:right;text-transform:uppercase;">{{ $order->payment_method }} ({{ $order->payment_status }})</td></tr>
            @if($order->delivery_agency_name)
            <tr><td style="padding:6px 0;color:#6b7280;">Delivery Agency</td><td style="text-align:right;">{{ $order->delivery_agency_name }}</td></tr>
            @endif
        </table>

        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="text-align:left;padding:8px;border-bottom:1px solid #e5e7eb;">Item</th>
                    <th style="text-align:center;padding:8px;border-bottom:1px solid #e5e7eb;">Qty</th>
                    <th style="text-align:right;padding:8px;border-bottom:1px solid #e5e7eb;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;">{{ $item['name'] ?? 'Item' }}</td>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;text-align:center;">{{ $item['qty'] ?? 1 }}</td>
                    <td style="padding:8px;border-bottom:1px solid #f1f5f9;text-align:right;">{{ $order->currency }} {{ number_format($item['subtotal'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($recipientRole === 'superadmin')
        <table style="width:100%;border-collapse:collapse;font-size:14px;margin-top:12px;">
            <tr><td style="padding:4px 0;color:#6b7280;">Subtotal</td><td style="text-align:right;">{{ $order->currency }} {{ number_format($order->subtotal, 2) }}</td></tr>
            <tr><td style="padding:4px 0;color:#6b7280;">Platform Fee</td><td style="text-align:right;">{{ $order->currency }} {{ number_format($order->platform_fee, 2) }}</td></tr>
            <tr><td style="padding:4px 0;color:#6b7280;">Delivery Fee</td><td style="text-align:right;">{{ $order->currency }} {{ number_format($order->delivery_fee, 2) }}</td></tr>
            <tr><td style="padding:6px 0;font-weight:bold;">Total</td><td style="text-align:right;font-weight:bold;">{{ $order->currency }} {{ number_format($order->total, 2) }}</td></tr>
        </table>
        @endif

        @if($order->note)
        <p style="background:#fffbeb;border-left:3px solid #f59e0b;padding:10px 12px;font-size:13px;">Customer note: {{ $order->note }}</p>
        @endif
    </div>
    <div style="background:#f8fafc;padding:14px 24px;font-size:12px;color:#94a3b8;">
        This is an automated notification from {{ $senderName }}.
    </div>
</div>
</body>
</html>
