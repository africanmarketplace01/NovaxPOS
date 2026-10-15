<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome to {{ $tenant_data['superadmin_company_name'] }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrap { max-width:600px; margin:32px auto; background:#fff; border-radius:16px; overflow:hidden;
          box-shadow:0 4px 24px rgba(0,0,0,.08); }
  .header { background:linear-gradient(135deg,#0f172a,#0066CC); padding:36px 40px; text-align:center; }
  .header h1 { margin:0; color:#fff; font-size:22px; font-weight:700; letter-spacing:-.3px; }
  .header p  { margin:6px 0 0; color:rgba(255,255,255,.7); font-size:14px; }
  .body { padding:36px 40px; }
  .greeting { font-size:18px; font-weight:600; margin-bottom:8px; }
  .intro { color:#64748b; font-size:14px; line-height:1.6; margin-bottom:28px; }
  .creds-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px 24px; margin-bottom:28px; }
  .creds-box h3 { margin:0 0 14px; font-size:13px; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; }
  .cred-row { display:flex; justify-content:space-between; align-items:center;
               padding:8px 0; border-bottom:1px solid #e2e8f0; font-size:14px; }
  .cred-row:last-child { border-bottom:none; padding-bottom:0; }
  .cred-label { color:#64748b; font-weight:500; }
  .cred-value { font-weight:600; color:#0f172a; font-family:monospace; font-size:14px; }
  .btn-wrap { text-align:center; margin-bottom:28px; }
  .btn { display:inline-block; background:#0066CC; color:#fff; text-decoration:none;
          padding:13px 32px; border-radius:8px; font-weight:700; font-size:15px; }
  .notice { background:#fefce8; border:1px solid #fde68a; border-radius:8px; padding:14px 18px;
             font-size:13px; color:#92400e; margin-bottom:28px; line-height:1.5; }
  .notice strong { color:#78350f; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:20px 40px; text-align:center;
             font-size:12px; color:#94a3b8; }
  .footer a { color:#0066CC; text-decoration:none; }
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <h1>🎉 Welcome to {{ $tenant_data['superadmin_company_name'] }}!</h1>
    <p>Your POS account is ready — let's get started.</p>
  </div>

  <div class="body">
    <div class="greeting">Hi {{ $tenant_data['name'] }},</div>
    <p class="intro">
      Your business account has been successfully created on <strong>{{ $tenant_data['superadmin_company_name'] }}</strong>.
      Below are your login details — please keep them safe.
    </p>

    <div class="creds-box">
      <h3>Your Login Credentials</h3>
      <div class="cred-row">
        <span class="cred-label">Store URL</span>
        <span class="cred-value">{{ $tenant_data['subdomain'] }}.{{ env('CENTRAL_DOMAIN', 'novaxpos.com') }}</span>
      </div>
      <div class="cred-row">
        <span class="cred-label">Email / Username</span>
        <span class="cred-value">{{ $tenant_data['email'] }}</span>
      </div>
      @if(!empty($tenant_data['password']))
      <div class="cred-row">
        <span class="cred-label">Password</span>
        <span class="cred-value">{{ $tenant_data['password'] }}</span>
      </div>
      @else
      <div class="cred-row">
        <span class="cred-label">Password</span>
        <span class="cred-value" style="font-family:sans-serif;font-size:12px;">
          <a href="{{ $tenant_data['password_reset_url'] ?? $tenant_data['login_url'] }}"
             style="color:#0066CC;text-decoration:none;font-weight:600;">
            Click here to set / reset your password &rarr;
          </a>
        </span>
      </div>
      @endif
      @if(!empty($tenant_data['subscription_type']))
      <div class="cred-row">
        <span class="cred-label">Plan</span>
        <span class="cred-value">{{ ucwords(str_replace('_', ' ', $tenant_data['subscription_type'])) }}</span>
      </div>
      @endif
      @if(!empty($tenant_data['expiry_date']))
      <div class="cred-row">
        <span class="cred-label">Active Until</span>
        <span class="cred-value">{{ $tenant_data['expiry_date'] }}</span>
      </div>
      @endif
    </div>

    <div class="btn-wrap">
      <a href="{{ $tenant_data['login_url'] }}" class="btn">Log In to Your POS &rarr;</a>
    </div>

    <div class="notice">
      @if(!empty($tenant_data['password']))
        <strong>Security tip:</strong> We recommend changing your password after your first login.
        Never share your credentials with anyone.
      @else
        <strong>Note:</strong> For security reasons, your password is not included in this email.
        Use the link above to set or reset your password before logging in.
      @endif
    </div>

    <p style="font-size:14px;color:#64748b;line-height:1.6;">
      If you have any questions or need help getting started, feel free to reach out to us at
      <a href="mailto:{{ $tenant_data['superadmin_email'] }}" style="color:#0066CC;">{{ $tenant_data['superadmin_email'] }}</a>.
    </p>
  </div>

  <div class="footer">
    &copy; {{ date('Y') }} {{ $tenant_data['superadmin_company_name'] }}. All rights reserved.<br>
    <a href="http://{{ env('CENTRAL_DOMAIN', 'novaxpos.com') }}">{{ env('CENTRAL_DOMAIN', 'novaxpos.com') }}</a>
  </div>

</div>
</body>
</html>
