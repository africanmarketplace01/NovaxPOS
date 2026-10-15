<?php

namespace App\Services\Marketplace;

use App\Models\landlord\MailSetting;
use App\Models\landlord\MarketplaceCustomer;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\SourcingMessage;
use App\Models\landlord\SourcingRequest;
use App\Models\landlord\SourcingResponse;
use App\Models\landlord\Tenant;
use App\Traits\MailInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Notifies vendors and customers about the product-sourcing workflow:
 *   new request  → email + in-app to every vendor (tenant) with listings
 *   new message  → email + in-app to the other party in the thread
 *
 * Every channel is individually guarded so one failure never blocks the rest.
 */
class SourcingNotifier
{
    use MailInfo;

    protected string $siteName;
    protected string $fromEmail;
    protected string $baseUrl;

    protected function boot(): void
    {
        $settings        = SaasSettings::allCached();
        $this->siteName  = $settings['mail_from_name'] ?? $settings['site_name'] ?? $settings['site_title'] ?? 'Marketplace';
        $this->fromEmail = $settings['mail_from_address'] ?? config('mail.from.address') ?? 'noreply@example.com';
        $this->baseUrl   = rtrim(config('app.url'), '/');

        // Prefer the SaaS SMTP settings (Settings → Mail — the same source the
        // "Test Email" button uses). Fall back to the legacy mail_settings table
        // only if the SaaS SMTP host isn't configured.
        try {
            if (!$this->setMailInfoFromSaasSettings()) {
                if ($ms = MailSetting::latest()->first()) {
                    $this->setMailInfo($ms);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SourcingNotifier: mail settings unavailable — ' . $e->getMessage());
        }
    }

    // ── New request → all vendors ───────────────────────────────────────────
    public function notifyNewRequest(SourcingRequest $req): void
    {
        $this->boot();

        $tenantIds = MarketplaceListing::visible()->distinct()->pluck('tenant_id')->filter()->unique();
        $subject   = "New product request: {$req->title}";
        $body      = "A customer is looking for a product and every vendor can respond.\n\n"
                   . "What they need: {$req->title}\n"
                   . Str::limit($req->description, 400) . "\n"
                   . ($req->budgetLabel() ? "Budget: {$req->budgetLabel()}\n" : '')
                   . "Quantity: {$req->quantity}\n\n"
                   . "Log in to your POS → Marketplace Requests to respond and chat with the customer.";

        foreach ($tenantIds as $tenantId) {
            try {
                $tenant = Tenant::find($tenantId);
                if (!$tenant) continue;

                if ($tenant->email) {
                    try {
                        Mail::raw($body, function ($m) use ($tenant, $subject) {
                            $m->to($tenant->email)->subject($subject)->from($this->fromEmail, $this->siteName);
                        });
                    } catch (\Throwable $e) {
                        Log::error("SourcingNotifier email failed ({$tenantId}): " . $e->getMessage());
                    }
                }

                $this->tenantInApp($tenant, "New product request: " . Str::limit($req->title, 60), '/marketplace-requests');
                $this->landlordAudit('tenant', (string) $tenantId, $subject);
            } catch (\Throwable $e) {
                Log::error("SourcingNotifier request block failed ({$tenantId}): " . $e->getMessage());
            }
        }

        // Push to every vendor device (OneSignal tag role=vendor)
        try {
            \App\Services\PushSender::toTag('role', 'vendor', 'New product request',
                Str::limit($req->title, 60), ['type' => 'sourcing_request', 'request_id' => $req->id]);
        } catch (\Throwable $e) {
            Log::error('SourcingNotifier push (request) failed: ' . $e->getMessage());
        }
    }

    // ── New chat message → the other party ─────────────────────────────────
    public function notifyNewMessage(SourcingResponse $thread, SourcingMessage $msg): void
    {
        $this->boot();

        if ($msg->sender_type === 'customer') {
            // Notify the vendor (tenant)
            try {
                $tenant = Tenant::find($thread->tenant_id);
                if ($tenant) {
                    if ($tenant->email) {
                        $body = "You have a new message from a customer about a product request.\n\n"
                              . "\"{$msg->body}\"\n\nReply from your POS → Marketplace Requests.";
                        Mail::raw($body, function ($m) use ($tenant) {
                            $m->to($tenant->email)->subject('New message on a product request')->from($this->fromEmail, $this->siteName);
                        });
                    }
                    $this->tenantInApp($tenant, 'New message on a product request', '/marketplace-requests');
                }
                \App\Services\PushSender::toVendorTenant((string) $thread->tenant_id,
                    'New message on a request', Str::limit($msg->body ?? '', 80), ['type' => 'sourcing_message']);
            } catch (\Throwable $e) {
                Log::error('SourcingNotifier vendor message notify failed: ' . $e->getMessage());
            }
        } else {
            // Notify the customer by email
            try {
                $req = SourcingRequest::find($thread->request_id);
                $customer = $req ? MarketplaceCustomer::find($req->customer_id) : null;
                if ($customer && $customer->email) {
                    $link = $this->baseUrl . '/account/requests/' . $thread->request_id;
                    $body = "{$thread->tenant_name} replied to your product request.\n\n"
                          . "\"{$msg->body}\"\n\nView & reply: {$link}";
                    Mail::raw($body, function ($m) use ($customer) {
                        $m->to($customer->email)->subject('A vendor replied to your request')->from($this->fromEmail, $this->siteName);
                    });
                }
                if ($req) {
                    \App\Services\PushSender::toCustomer((int) $req->customer_id,
                        ($thread->tenant_name ?: 'A vendor') . ' replied', Str::limit($msg->body ?? '', 80),
                        ['type' => 'sourcing_message', 'request_id' => $thread->request_id]);
                }
            } catch (\Throwable $e) {
                Log::error('SourcingNotifier customer message notify failed: ' . $e->getMessage());
            }
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────────
    protected function tenantInApp(Tenant $tenant, string $message, string $link): void
    {
        try {
            $tenant->run(function () use ($message, $link) {
                $adminIds = DB::table('users')
                    ->whereIn('role_id', [1, 2])
                    ->where('is_active', 1)->where('is_deleted', 0)
                    ->pluck('id');
                $rows = [];
                foreach ($adminIds as $uid) {
                    $rows[] = [
                        'id'              => (string) Str::uuid(),
                        'type'            => 'App\\Notifications\\SourcingRequestNotification',
                        'notifiable_type' => 'App\\Models\\User',
                        'notifiable_id'   => $uid,
                        'data'            => json_encode(['kind' => 'sourcing', 'message' => $message, 'link' => $link]),
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }
                if ($rows) {
                    DB::table('notifications')->insert($rows);
                }
            });
        } catch (\Throwable $e) {
            Log::error('SourcingNotifier tenant in-app failed: ' . $e->getMessage());
        }
    }

    protected function landlordAudit(string $type, ?string $recipientId, string $title): void
    {
        try {
            DB::connection('saleprosaas_landlord')->table('marketplace_notifications')->insert([
                'recipient_type' => $type,
                'recipient_id'   => $recipientId,
                'order_number'   => null,
                'title'          => $title,
                'body'           => 'Product sourcing request',
                'link'           => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SourcingNotifier landlord audit failed: ' . $e->getMessage());
        }
    }
}
