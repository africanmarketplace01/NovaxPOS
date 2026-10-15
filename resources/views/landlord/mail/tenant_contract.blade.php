<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ $contract->getDocumentTypeLabel() }}</title>
  <style>
    body { margin:0; padding:0; background:#f1f5f9; font-family: Arial, sans-serif; }
    .wrapper { max-width:600px; margin:32px auto; background:#fff; border-radius:10px; overflow:hidden;
               box-shadow:0 2px 12px rgba(0,0,0,.08); }
    .header { background:#1a3c6b; padding:28px 32px; text-align:center; }
    .header h1 { color:#fff; font-size:20px; margin:0 0 4px 0; }
    .header p  { color:rgba(255,255,255,.65); font-size:13px; margin:0; }
    .body { padding:28px 32px; }
    .body p { color:#374151; font-size:14px; line-height:1.6; margin:0 0 14px 0; }
    .highlight { background:#eef2ff; border-left:4px solid #1a3c6b; padding:14px 18px;
                 border-radius:4px; margin:18px 0; }
    .highlight strong { color:#1a3c6b; }
    .btn { display:inline-block; background:#1a3c6b; color:#fff; text-decoration:none;
           padding:12px 28px; border-radius:6px; font-weight:bold; font-size:14px; margin:10px 0; }
    .doc-badge { display:inline-block; background:#2c5282; color:#fff; padding:4px 12px;
                 border-radius:12px; font-size:12px; font-weight:bold; margin-bottom:12px; }
    .footer { background:#f8fafc; padding:20px 32px; text-align:center; border-top:1px solid #e2e8f0; }
    .footer p { color:#94a3b8; font-size:12px; margin:0; }
  </style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{{ $senderName }}</h1>
    <p>Official Document Delivery</p>
  </div>
  <div class="body">
    <div class="doc-badge">{{ $contract->getDocumentTypeLabel() }}</div>

    <p>Dear <strong>{{ $contract->tenant->company_name ?? $contract->tenant_id }}</strong>,</p>

    <p>Please find attached your <strong>{{ $contract->getDocumentTypeLabel() }}</strong>, effective <strong>{{ $contract->effective_date?->format('F j, Y') }}</strong>.</p>

    <div class="highlight">
      @if($contract->contract_amount)
      <p style="margin:0 0 8px 0;"><strong>Contract Value:</strong> USD {{ number_format($contract->contract_amount, 2) }}</p>
      <p style="margin:0 0 8px 0;"><strong>Deposit ({{ $contract->deposit_percent }}%):</strong> USD {{ number_format($contract->getDepositAmount(), 2) }} — due before work begins</p>
      <p style="margin:0;"><strong>Balance ({{ $contract->balance_percent }}%):</strong> USD {{ number_format($contract->getBalanceAmount(), 2) }} — due on delivery</p>
      @else
      <p style="margin:0;"><strong>Document Type:</strong> {{ $contract->getDocumentTypeLabel() }}</p>
      @endif
    </div>

    <p>Please review the attached PDF document carefully. Once you have read and agreed to the terms, please sign and return a copy via email or arrange for an in-person signing.</p>

    <p>If you have any questions or require clarification on any section, please don't hesitate to reach out to us at <strong>{{ $senderEmail }}</strong>.</p>

    <p>Thank you for choosing {{ $senderName }}. We look forward to a successful partnership.</p>

    <p>Best regards,<br>
    <strong>Omaru A. Kamara</strong><br>
    CEO, {{ $senderName }}<br>
    {{ $senderEmail }}</p>
  </div>
  <div class="footer">
    <p>This email was sent by {{ $senderName }} &bull; Confidential</p>
    <p>The attached PDF is the official document. Please keep a copy for your records.</p>
  </div>
</div>
</body>
</html>
