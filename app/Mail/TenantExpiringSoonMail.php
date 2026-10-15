<?php

namespace App\Mail;

use App\Models\landlord\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantExpiringSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant, public int $daysLeft) {}

    public function build()
    {
        $renewalUrl = env('RENEWAL_URL', 'https://' . env('CENTRAL_DOMAIN') . '/pricing');

        return $this->subject("Action Required: Your NovaxPOS subscription expires in {$this->daysLeft} days")
            ->html("
<!DOCTYPE html><html><body style='font-family:sans-serif;max-width:600px;margin:auto;padding:20px;'>
    <h2 style='color:#f59e0b;'>Subscription Expiring Soon</h2>
    <p>Hi <strong>{$this->tenant->company_name}</strong>,</p>
    <p>Your NovaxPOS subscription expires in <strong>{$this->daysLeft} days</strong>
       on <strong>{$this->tenant->expiry_date->format('d M Y')}</strong>.</p>
    <p>Renew now to keep uninterrupted access:</p>
    <a href='{$renewalUrl}' style='background:#6366f1;color:#fff;padding:12px 24px;border-radius:6px;
       text-decoration:none;display:inline-block;'>Renew Now</a>
    <p style='margin-top:24px;color:#64748b;font-size:.85rem;'>
        Questions? Reply to this email or open a support ticket.</p>
</body></html>
            ");
    }
}
