<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceListing extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'marketplace_listings';

    protected $fillable = [
        'tenant_id', 'product_id', 'tenant_name', 'tenant_store_name',
        'name', 'slug', 'description', 'image_url', 'image_urls', 'category', 'brand',
        'price', 'promo_price', 'currency', 'stock_qty', 'in_stock',
        'is_active', 'approved', 'synced_at',
    ];

    protected $casts = [
        'price'      => 'float',
        'promo_price' => 'float',
        'stock_qty'  => 'integer',
        'in_stock'   => 'boolean',
        'is_active'  => 'boolean',
        'approved'   => 'boolean',
        'image_urls' => 'array',
        'synced_at'  => 'datetime',
    ];

    /**
     * Gallery images for the product detail page.
     * Falls back to the single image_url for listings synced before multi-image support.
     *
     * @return array<int,string>
     */
    public function galleryImages(): array
    {
        $imgs = $this->image_urls;
        if (is_string($imgs)) {
            $decoded = json_decode($imgs, true);
            $imgs = is_array($decoded) ? $decoded : null;
        }
        if (empty($imgs)) {
            $imgs = array_filter([$this->image_url]);
        }
        return array_values(array_unique(array_filter($imgs)));
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('approved', true)
            // Sellers on a fee hold (overdue platform-fee invoice) drop off the
            // marketplace until it is paid; see marketplace:enforce-fee-holds.
            ->whereNotExists(function ($q) {
                $q->from('tenants')
                    ->whereColumn('tenants.id', 'marketplace_listings.tenant_id')
                    ->where('tenants.marketplace_hold', true);
            });
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('in_stock', true)->where('stock_qty', '>', 0);
    }

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Effective selling price (promo_price if set, else price).
     */
    public function effectivePrice(): float
    {
        return $this->promo_price ?? $this->price;
    }

    /**
     * Upsert listing data from a synced product array.
     */
    public static function syncProduct(string $tenantId, array $product): self
    {
        return static::updateOrCreate(
            ['tenant_id' => $tenantId, 'product_id' => $product['id']],
            array_merge($product, ['synced_at' => now()])
        );
    }
}
