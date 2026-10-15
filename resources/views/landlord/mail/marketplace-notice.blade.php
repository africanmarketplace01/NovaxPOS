<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;margin:0;padding:24px;">
<div style="max-width:640px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
    <div style="background:#0f172a;color:#fff;padding:18px 24px;">
        <h2 style="margin:0;font-size:18px;">{{ $senderName }} — Marketplace</h2>
    </div>
    <div style="padding:24px;">
        <p style="margin-top:0;">Hello {{ $recipientName }},</p>
        <p style="font-size:16px;font-weight:bold;">{{ $headline }}</p>
        @foreach ($lines as $line)
            <p>{{ $line }}</p>
        @endforeach

        @if (count($facts))
            <table style="width:100%;border-collapse:collapse;font-size:14px;margin:14px 0;">
                @foreach ($facts as $label => $value)
                    <tr>
                        <td style="padding:5px 0;color:#6b7280;">{{ $label }}</td>
                        <td style="text-align:right;font-weight:bold;">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <p style="margin:22px 0;">
            <a href="{{ $actionUrl }}" style="display:inline-block;background:#0066cc;color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;font-weight:bold;">Open Platform Fees</a>
        </p>
        <p style="color:#6b7280;font-size:12px;margin-bottom:0;">You are receiving this because your store sells on the {{ $senderName }} marketplace.</p>
    </div>
</div>
</body>
</html>
