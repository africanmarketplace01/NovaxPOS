<?php

namespace App\Services;

use App\Models\landlord\SaasSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications through OneSignal (REST API). Reuses the platform
 * OneSignal keys stored in SaaS settings (onesignal_app_id / onesignal_rest_api_key).
 *
 * Targeting matches the identities set by the mobile apps:
 *   customers  → external id "customer:{id}"
 *   vendors    → tag  role=vendor, tenant={tenantId}
 *
 * Every call is guarded; push is silently skipped when keys aren't configured.
 */
class PushSender
{
    protected static function creds(): ?array
    {
        try {
            $s = SaasSettings::allCached();
        } catch (\Throwable $e) {
            return null;
        }
        $appId  = $s['onesignal_app_id']       ?? null;
        $apiKey = $s['onesignal_rest_api_key'] ?? null;
        if (!$appId || !$apiKey) return null;
        return [$appId, $apiKey];
    }

    protected static function post(array $payload): void
    {
        $creds = static::creds();
        if (!$creds) return;
        [$appId, $apiKey] = $creds;

        try {
            Http::withHeaders(['Authorization' => 'Basic ' . $apiKey])
                ->timeout(10)
                ->post('https://onesignal.com/api/v1/notifications', array_merge([
                    'app_id'         => $appId,
                    'target_channel' => 'push',
                ], $payload));
        } catch (\Throwable $e) {
            Log::error('PushSender failed: ' . $e->getMessage());
        }
    }

    /** Send to specific external ids (e.g. ["customer:12"]). */
    public static function toExternalIds(array $externalIds, string $title, string $body, array $data = []): void
    {
        if (empty($externalIds)) return;
        static::post([
            'include_aliases' => ['external_id' => array_values($externalIds)],
            'headings'        => ['en' => $title],
            'contents'        => ['en' => $body],
            'data'            => $data,
        ]);
    }

    /** Send to every device carrying a tag key=value (e.g. role=vendor). */
    public static function toTag(string $key, string $value, string $title, string $body, array $data = []): void
    {
        static::post([
            'filters'  => [['field' => 'tag', 'key' => $key, 'relation' => '=', 'value' => $value]],
            'headings' => ['en' => $title],
            'contents' => ['en' => $body],
            'data'     => $data,
        ]);
    }

    /** Send to all vendor devices belonging to one tenant/store. */
    public static function toVendorTenant(string $tenantId, string $title, string $body, array $data = []): void
    {
        static::post([
            'filters' => [
                ['field' => 'tag', 'key' => 'role', 'relation' => '=', 'value' => 'vendor'],
                ['operator' => 'AND'],
                ['field' => 'tag', 'key' => 'tenant', 'relation' => '=', 'value' => $tenantId],
            ],
            'headings' => ['en' => $title],
            'contents' => ['en' => $body],
            'data'     => $data,
        ]);
    }

    public static function toCustomer(int $customerId, string $title, string $body, array $data = []): void
    {
        static::toExternalIds(['customer:' . $customerId], $title, $body, $data);
    }
}
