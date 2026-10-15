<?php

namespace App\Mail;

use App\Models\landlord\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function build()
    {
        $renewalUrl = env('RENEWAL_URL', 'https://' . env('CENTRAL_DOMAIN') . '/pricing');

        return $this->subject('Your NovaxPOS Subscription Has Expired')
            ->html("
<!DOCTYPE html><html><body style='font-family:sans-serif;max-width:600px;margin:auto;padding:20px;'>
    <h2 style='color:#dc2626;'>Subscription Expired</h2>
    <p>Hi <strong>{$this->tenant->company_name}</strong>,</p>
    <p>Your NovaxPOS subscription expired on <strong>{$this->tenant->expiry_date->format('d M Y')}</strong>
       and your account has been suspended.</p>
    <p>To restore access, please renew your subscription:</p>
    <a href='{$renewalUrl}' style='background:#6366f1;color:#fff;padding:12px 24px;border-radius:6px;
       text-decoration:none;display:inline-block;'>Renew Subscription</a>
    <p style='margin-top:24px;color:#64748b;font-size:.85rem;'>
        If you have any questions, reply to this email or open a support ticket.</p>
</body></html>
            ");
    }
}
