<?php

namespace App\Services\Marketplace;

use App\Mail\MarketplaceNoticeMail;
use App\Models\landlord\MailSetting;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\Tenant;
use App\Traits\MailInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Tells a vendor about something that happened to their marketplace account:
 * an in-app notification for the store's admins plus an email to the owner.
 * Used for fee holds placed and lifted, and payouts sent.
 *
 * Never throws: a notice failing to send must not undo the action behind it.
 */
class VendorNotifier
{
    use MailInfo;

    /**
     * @param  array<int, string>  $lines  short paragraphs for the email body
     * @param  array<string, string>  $facts  label => value rows (reference, amount…)
     */
    public static function send(Tenant $tenant, string $subject, string $message, array $lines = [], array $facts = []): void
    {
        $link = self::feesUrl($tenant);
        self::inApp($tenant, $message, $link);
        self::email($tenant, $subject, $message, $lines, $facts, $link);
    }

    private static function inApp(Tenant $tenant, string $message, string $link): void
    {
        try {
            $tenant->run(function () use ($message, $link) {
                $adminIds = DB::table('users')->whereIn('role_id', [1, 2])
                    ->where('is_active', 1)->where('is_deleted', 0)->pluck('id');
                $rows = $adminIds->map(fn ($uid) => [
                    'id'              => (string) Str::uuid(),
                    'type'            => 'App\\Notifications\\MarketplaceNotice',
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id'   => $uid,
                    // 'marketplace_order' is the kind the store's notification bell shows.
                    'data'            => json_encode(['kind' => 'marketplace_order', 'message' => $message, 'link' => $link]),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ])->all();
                if ($rows) {
                    DB::table('notifications')->insert($rows);
                }
            });
        } catch (\Throwable $e) {
            Log::warning("VendorNotifier in-app failed for {$tenant->id}: " . $e->getMessage());
        }
    }

    private static function email(Tenant $tenant, string $subject, string $message, array $lines, array $facts, string $link): void
    {
        if (! $tenant->email) {
            return;
        }

        try {
            // Same mail source as fee-request emails: SaaS SMTP settings, then the legacy table.
            $mailer = new self();
            if (! $mailer->setMailInfoFromSaasSettings()) {
                $legacy = MailSetting::latest()->first();
                if ($legacy) {
                    $mailer->setMailInfo($legacy);
                }
            }

            $settings = SaasSettings::allCached();
            Mail::to($tenant->email)->send(new MarketplaceNoticeMail(
                subjectLine: $subject,
                headline: $message,
                lines: $lines,
                facts: $facts,
                actionUrl: $link,
                recipientName: $tenant->company_name ?: $tenant->id,
                senderName: $settings['site_name'] ?? config('app.name', 'NovaxPOS'),
                senderEmail: $settings['mail_from_address'] ?? config('mail.from.address') ?? 'noreply@example.com',
            ));
        } catch (\Throwable $e) {
            Log::warning("VendorNotifier email failed for {$tenant->id}: " . $e->getMessage());
        }
    }

    /** The store's Platform Fees page, where holds, invoices and payouts are listed. */
    private static function feesUrl(Tenant $tenant): string
    {
        return 'http://' . $tenant->id . '.' . env('CENTRAL_DOMAIN') . '/backend/store/platform-fees';
    }
}
