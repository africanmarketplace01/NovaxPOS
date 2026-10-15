<?php

namespace App\Services\Marketplace;

use App\Mail\MarketplaceOrderMail;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\MailSetting;
use App\Models\landlord\MarketplaceOrder;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\SuperadminUser;
use App\Models\landlord\Tenant;
use App\Traits\MailInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Notifies every party about a new marketplace order:
 *   vendors (tenants)      → email + in-app (tenant notifications table)
 *   delivery agency        → email + in-app (landlord marketplace_notifications)
 *   superadmins            → email + in-app (landlord marketplace_notifications)
 *   OneSignal push         → sent when platform keys are configured (skipped otherwise)
 *
 * Every channel is individually guarded — one failing channel never blocks the rest.
 */
class OrderNotifier
{
    use MailInfo;

    protected string $senderName;
    protected string $senderEmail;

    public function send(MarketplaceOrder $order): void
    {
        $settings          = SaasSettings::allCached();
        $this->senderName  = $settings['site_name'] ?? 'NovaxSuites POS';
        $this->senderEmail = $settings['mail_from_address'] ?? config('mail.from.address') ?? 'noreply@example.com';

        // Configure SMTP from landlord mail settings (same pattern as contract emails)
        try {
            $mailSetting = MailSetting::latest()->first();
            if ($mailSetting) {
                $this->setMailInfo($mailSetting);
            }
        } catch (\Throwable $e) {
            Log::warning('OrderNotifier: mail settings unavailable — ' . $e->getMessage());
        }

        $byTenant = collect($order->items)->groupBy('tenant_id');

        $this->notifyVendors($order, $byTenant);
        $this->notifyAgency($order);
        $this->notifySuperadmins($order);
        $this->pushOneSignal($order, $settings);
    }

    // ── Vendors ────────────────────────────────────────────────────────────

    protected function notifyVendors(MarketplaceOrder $order, $byTenant): void
    {
        foreach ($byTenant as $tenantId => $items) {
            $items = $items->toArray();

            try {
                $tenant = Tenant::find($tenantId);
                if (!$tenant) continue;

                // Email to the tenant's registered address
                if ($tenant->email) {
                    try {
                        Mail::to($tenant->email)->send(new MarketplaceOrderMail(
                            $order, 'vendor', $items,
                            $this->senderName, $this->senderEmail,
                            $tenant->company_name
                        ));
                    } catch (\Throwable $e) {
                        Log::error("OrderNotifier vendor email failed ({$tenantId}): " . $e->getMessage());
                    }
                }

                // In-app: Laravel database notification for the tenant's admin users
                try {
                    $orderNumber = $order->order_number;
                    $itemCount   = count($items);
                    $tenant->run(function () use ($orderNumber, $itemCount) {
                        $adminIds = DB::table('users')
                            ->whereIn('role_id', [1, 2])
                            ->where('is_active', 1)->where('is_deleted', 0)
                            ->pluck('id');
                        $rows = [];
                        foreach ($adminIds as $uid) {
                            $rows[] = [
                                'id'              => (string) Str::uuid(),
                                'type'            => 'App\\Notifications\\MarketplaceOrderPlaced',
                                'notifiable_type' => 'App\\Models\\User',
                                'notifiable_id'   => $uid,
                                'data'            => json_encode([
                                    'kind'         => 'marketplace_order',
                                    'message'      => "New marketplace order {$orderNumber} ({$itemCount} item(s))",
                                    'order_number' => $orderNumber,
                                ]),
                                'created_at'      => now(),
                                'updated_at'      => now(),
                            ];
                        }
                        if ($rows) {
                            DB::table('notifications')->insert($rows);
                        }
                    });
                } catch (\Throwable $e) {
                    Log::error("OrderNotifier vendor in-app failed ({$tenantId}): " . $e->getMessage());
                }

                // Push to the vendor's devices
                try {
                    \App\Services\PushSender::toVendorTenant((string) $tenantId, 'New marketplace order',
                        "Order {$order->order_number} — " . count($items) . ' item(s)',
                        ['type' => 'order', 'order_number' => $order->order_number]);
                } catch (\Throwable $e) {
                    Log::error("OrderNotifier push failed ({$tenantId}): " . $e->getMessage());
                }

                // Audit copy in landlord table
                $this->landlordNotification('tenant', $tenantId, $order,
                    "Order {$order->order_number} routed to " . ($tenant->company_name ?? $tenantId));
            } catch (\Throwable $e) {
                Log::error("OrderNotifier vendor block failed ({$tenantId}): " . $e->getMessage());
            }
        }
    }

    // ── Delivery agency ────────────────────────────────────────────────────

    protected function notifyAgency(MarketplaceOrder $order): void
    {
        if (!$order->delivery_agency_id) return;

        try {
            $agency = DeliveryAgency::find($order->delivery_agency_id);
            if (!$agency) return;

            if ($agency->email) {
                try {
                    Mail::to($agency->email)->send(new MarketplaceOrderMail(
                        $order, 'agency', $order->items,
                        $this->senderName, $this->senderEmail,
                        $agency->name
                    ));
                } catch (\Throwable $e) {
                    Log::error('OrderNotifier agency email failed: ' . $e->getMessage());
                }
            }

            $this->landlordNotification('agency', (string) $agency->id, $order,
                "New delivery request — order {$order->order_number} to {$order->customer_address}");
        } catch (\Throwable $e) {
            Log::error('OrderNotifier agency block failed: ' . $e->getMessage());
        }
    }

    // ── Superadmins ────────────────────────────────────────────────────────

    protected function notifySuperadmins(MarketplaceOrder $order): void
    {
        try {
            $emails = SuperadminUser::pluck('email')->filter()->unique()->values()->all();
            foreach ($emails as $email) {
                try {
                    Mail::to($email)->send(new MarketplaceOrderMail(
                        $order, 'superadmin', $order->items,
                        $this->senderName, $this->senderEmail
                    ));
                } catch (\Throwable $e) {
                    Log::error('OrderNotifier superadmin email failed: ' . $e->getMessage());
                }
            }

            $this->landlordNotification('superadmin', null, $order,
                "New marketplace order {$order->order_number} — {$order->currency} " . number_format((float) $order->total, 2));
        } catch (\Throwable $e) {
            Log::error('OrderNotifier superadmin block failed: ' . $e->getMessage());
        }
    }

    // ── OneSignal push (skipped until platform keys are configured) ────────

    protected function pushOneSignal(MarketplaceOrder $order, array $settings): void
    {
        $appId  = $settings['onesignal_app_id']       ?? null;
        $apiKey = $settings['onesignal_rest_api_key'] ?? null;
        if (!$appId || !$apiKey) return;

        try {
            Http::withHeaders(['Authorization' => 'Basic ' . $apiKey])
                ->timeout(10)
                ->post('https://onesignal.com/api/v1/notifications', [
                    'app_id'            => $appId,
                    'included_segments' => ['Subscribed Users'],
                    'headings'          => ['en' => 'New Marketplace Order'],
                    'contents'          => ['en' => "Order {$order->order_number} — {$order->currency} " . number_format((float) $order->total, 2)],
                ]);
        } catch (\Throwable $e) {
            Log::error('OrderNotifier OneSignal failed: ' . $e->getMessage());
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    protected function landlordNotification(string $type, ?string $recipientId, MarketplaceOrder $order, string $title): void
    {
        try {
            DB::connection('saleprosaas_landlord')->table('marketplace_notifications')->insert([
                'recipient_type' => $type,
                'recipient_id'   => $recipientId,
                'order_number'   => $order->order_number,
                'title'          => $title,
                'body'           => "Customer: {$order->customer_name} ({$order->customer_phone}) — {$order->customer_address}",
                'link'           => null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('OrderNotifier landlord notification failed: ' . $e->getMessage());
        }
    }
}
