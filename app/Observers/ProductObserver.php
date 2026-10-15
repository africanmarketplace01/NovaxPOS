<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\MarketplaceSyncService;
use Illuminate\Support\Facades\Log;

/**
 * Keeps marketplace listings in sync with tenant product changes.
 * Only refreshes products that are ALREADY listed — never auto-lists.
 * Wrapped in try/catch so marketplace issues can never break POS operations.
 */
class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->sync($product);
    }

    public function deleted(Product $product): void
    {
        try {
            if (! $this->inTenantContext()) {
                return;
            }
            $tenantId = tenancy()->tenant?->id ?? config('app.tenant_id');
            if ($tenantId) {
                app(MarketplaceSyncService::class)->deactivate($tenantId, $product->id);
            }
        } catch (\Throwable $e) {
            Log::debug('Marketplace listing deactivate skipped: ' . $e->getMessage());
        }
    }

    private function sync(Product $product): void
    {
        try {
            if (! $this->inTenantContext()) {
                return;
            }
            app(MarketplaceSyncService::class)->refreshIfListed($product);
        } catch (\Throwable $e) {
            // Landlord DB unreachable or schema mismatch — never block the POS
            Log::debug('Marketplace listing sync skipped: ' . $e->getMessage());
        }
    }

    private function inTenantContext(): bool
    {
        try {
            return config('database.connections.saleprosaas_landlord') && tenancy()->initialized;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
