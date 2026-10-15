<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\DB;

/**
 * Pushes tenant product snapshots to the landlord marketplace_listings table.
 * Used by the tenant listings page (manual toggle/sync) and the Product
 * observer (automatic refresh on product changes).
 */
class MarketplaceSyncService
{
    /**
     * Insert or refresh a listing row for the given product.
     */
    public function upsertListing(string $tenantId, Product $product, StoreSettings $store): void
    {
        $generalSetting = DB::table('general_settings')->latest()->first();
        $tenantName     = $generalSetting?->site_title ?? 'Unknown';

        // general_settings.currency stores a currency ID — resolve to symbol/code
        $currency = 'USD';
        if ($generalSetting?->currency) {
            $currencyRow = DB::table('currencies')->where('id', $generalSetting->currency)->first();
            $currency    = $currencyRow?->symbol ?? $currencyRow?->code ?? 'USD';
        }

        // products.image holds comma-separated filenames — build the full gallery
        $imageUrl   = null;
        $imageUrls  = [];
        foreach (explode(',', (string) $product->image) as $img) {
            $img = trim($img);
            if ($img === '' || $img === 'zummXD2dvAtI.png') {
                continue;
            }
            $imageUrls[] = asset('images/product/' . $img);
        }
        if (!empty($imageUrls)) {
            $imageUrl = $imageUrls[0];
        }

        $price      = (float) $product->price;
        $promoPrice = null;
        if ($product->promotion && $product->promotion_price > 0) {
            $now = now();
            if (
                (!$product->starting_date || $now->gte($product->starting_date)) &&
                (!$product->last_date     || $now->lte($product->last_date))
            ) {
                $promoPrice = (float) $product->promotion_price;
            }
        }

        DB::connection('saleprosaas_landlord')
            ->table('marketplace_listings')
            ->updateOrInsert(
                ['tenant_id' => $tenantId, 'product_id' => $product->id],
                [
                    'tenant_name'       => $tenantName,
                    'tenant_store_name' => $store->store_name ?? $tenantName,
                    'name'              => $product->name,
                    'slug'              => $product->slug,
                    'description'       => $product->short_description ?? $product->product_details,
                    'image_url'         => $imageUrl,
                    'image_urls'        => !empty($imageUrls) ? json_encode($imageUrls) : null,
                    'category'          => $product->category?->name,
                    'brand'             => $product->brand?->name,
                    'price'             => $price,
                    'promo_price'       => $promoPrice,
                    'currency'          => $currency,
                    'stock_qty'         => (int) $product->qty,
                    'in_stock'          => $product->qty > 0 || !$product->track_inventory,
                    'is_active'         => true,
                    'approved'          => true,
                    'synced_at'         => now(),
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );
    }

    /**
     * Refresh the listing for a product ONLY if it is already listed.
     * Never auto-lists new products — listing is the tenant's explicit choice.
     */
    public function refreshIfListed(Product $product): void
    {
        $tenantId = tenancy()->tenant?->id ?? config('app.tenant_id');
        if (! $tenantId) {
            return;
        }

        $landlord = DB::connection('saleprosaas_landlord')->table('marketplace_listings');

        $listed = $landlord
            ->where('tenant_id', $tenantId)
            ->where('product_id', $product->id)
            ->exists();

        if (! $listed) {
            return;
        }

        // Product deactivated or soft-deleted → hide the listing
        if (! $product->is_active || $product->is_deleted ?? false) {
            $this->deactivate($tenantId, $product->id);
            return;
        }

        $store = StoreSettings::instance();
        if (! $store->marketplace_listed) {
            return;
        }

        $this->upsertListing($tenantId, $product, $store);
    }

    /**
     * Hide a listing (product deleted/deactivated in the POS).
     */
    public function deactivate(string $tenantId, int $productId): void
    {
        DB::connection('saleprosaas_landlord')
            ->table('marketplace_listings')
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
}
