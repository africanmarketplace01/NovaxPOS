<?php

namespace App\Services\Marketplace;

use App\Models\landlord\SaasSettings;
use App\Models\landlord\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Hides a marketplace seller's listings while a platform-fee invoice is
 * overdue, and restores them once it is paid or cancelled.
 *
 * Overdue = a fee request in status "sent" whose due date (sent date +
 * seller_fee_due_days) plus the platform grace_days has passed. Only
 * marketplace-only sellers are held; subscribed merchants are never touched.
 *
 * The hold only removes listings from the marketplace (MarketplaceListing
 * scopeVisible); the seller can still sign in, manage products and pay.
 */
class FeeHolds
{
    /** Due date for a fee request sent now. */
    public static function dueDate(): string
    {
        $days = (int) (SaasSettings::allCached()['seller_fee_due_days'] ?? 7);

        return now()->addDays(max($days, 0))->toDateString();
    }

    /** Re-evaluate one tenant. Returns true when the tenant is now on hold. */
    public static function sync(string $tenantId): bool
    {
        if (! self::ready()) {
            return false;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return false;
        }

        $overdue = $tenant->isMarketplaceSeller() ? self::overdueReference($tenantId) : null;

        if ($overdue && ! $tenant->marketplace_hold) {
            $tenant->update([
                'marketplace_hold' => true,
                'marketplace_hold_reason' => "Platform fee invoice {$overdue} is overdue",
                'marketplace_hold_at' => now(),
            ]);
            Log::info("Marketplace fee hold placed on {$tenantId} ({$overdue}).");

            $invoice = self::invoice($overdue);
            VendorNotifier::send(
                $tenant,
                "Your marketplace listings are paused — invoice {$overdue} is overdue",
                "Your marketplace listings are hidden until fee invoice {$overdue} is paid.",
                [
                    'Shoppers can no longer see your products on the marketplace. Your store, products and orders are safe and you can still sign in.',
                    'Pay the invoice and your listings come back as soon as we confirm the payment.',
                ],
                array_filter([
                    'Invoice' => $overdue,
                    'Amount due' => $invoice ? $invoice->currency . ' ' . number_format((float) $invoice->fee_amount, 2) : null,
                    'Was due' => $invoice && $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : null,
                ])
            );
        } elseif (! $overdue && $tenant->marketplace_hold) {
            $tenant->update([
                'marketplace_hold' => false,
                'marketplace_hold_reason' => null,
                'marketplace_hold_at' => null,
            ]);
            Log::info("Marketplace fee hold lifted for {$tenantId}.");

            VendorNotifier::send(
                $tenant,
                'Your marketplace listings are live again',
                'Thank you — your marketplace listings are visible to shoppers again.',
                ['Your fee invoice has been settled, so your products are back on the marketplace.']
            );
        }

        return (bool) $overdue;
    }

    /** Re-evaluate every marketplace seller plus anyone currently held. */
    public static function syncAll(): array
    {
        if (! self::ready()) {
            return ['checked' => 0, 'held' => 0];
        }

        $ids = Tenant::where('subscription_type', Tenant::MARKETPLACE_SUBSCRIPTION)
            ->orWhere('marketplace_hold', true)
            ->pluck('id');

        $held = 0;
        foreach ($ids as $id) {
            $held += self::sync((string) $id) ? 1 : 0;
        }

        return ['checked' => $ids->count(), 'held' => $held];
    }

    private static function overdueReference(string $tenantId): ?string
    {
        $graceDays = (int) (SaasSettings::allCached()['grace_days'] ?? 3);

        return DB::connection('saleprosaas_landlord')->table('platform_fee_requests')
            ->where('tenant_id', $tenantId)
            ->where('status', 'sent')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->subDays($graceDays)->toDateString())
            ->orderBy('due_date')
            ->value('reference');
    }

    private static function invoice(string $reference): ?object
    {
        return DB::connection('saleprosaas_landlord')->table('platform_fee_requests')
            ->where('reference', $reference)->first();
    }

    /** The hold needs the migration's columns and the fee-request table. */
    private static function ready(): bool
    {
        $schema = Schema::connection('saleprosaas_landlord');

        return $schema->hasColumn('tenants', 'marketplace_hold')
            && $schema->hasTable('platform_fee_requests')
            && $schema->hasColumn('platform_fee_requests', 'due_date');
    }
}
