<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'packages';

    protected $fillable = [
        'name',
        'description',
        'price_monthly',
        'price_yearly',
        'price_onetime',    // null = one-time plan not offered
        'features',         // JSON array of feature keys from TenantInfo::features()
        'product_limit',    // null = unlimited
        'user_limit',       // null = unlimited
        'warehouse_limit',  // null = unlimited
        'is_free_trial',
        'trial_days',
        'allow_paid_trial', // allow a trial period on paid plans
        'is_active',
        'sort_order',
        'badge',            // e.g. "Most Popular"
        'show_on_public',   // whether this plan appears on landing/pricing pages
        'is_marketplace_seller', // the free commission-only plan (see marketplaceSeller())
    ];

    protected $casts = [
        'features'          => 'array',
        'price_monthly'     => 'float',
        'price_yearly'      => 'float',
        'price_onetime'     => 'float',
        'is_free_trial'     => 'boolean',
        'allow_paid_trial'  => 'boolean',
        'is_active'         => 'boolean',
        'show_on_public'    => 'boolean',
        'is_marketplace_seller' => 'boolean',
    ];

    /* ── Relationships ─────────────────────────────────────────────────────── */

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    /* ── Helpers ───────────────────────────────────────────────────────────── */

    public function hasFeature(string $key): bool
    {
        return in_array($key, $this->features ?? []);
    }

    public function getYearlySavingPercent(): int
    {
        if (!$this->price_monthly || !$this->price_yearly) return 0;
        $monthly12 = $this->price_monthly * 12;
        return (int) round((($monthly12 - $this->price_yearly) / $monthly12) * 100);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /** Only active plans that the superadmin has chosen to show on public pages */
    public function scopePublic($query)
    {
        return $query->where('is_active', true)
                     ->where('show_on_public', true)
                     ->orderBy('sort_order');
    }

    /**
     * The free plan marketplace-only sellers sign up on: no subscription, no
     * expiry, a commission on each marketplace order instead. Null until the
     * add_marketplace_seller_support migration has run.
     */
    public static function marketplaceSeller(): ?self
    {
        // Public pages call this; before the migration adds the column it must
        // quietly report "not available" instead of breaking the page.
        try {
            return static::where('is_marketplace_seller', true)->where('is_active', true)->first();
        } catch (\Illuminate\Database\QueryException $e) {
            return null;
        }
    }
}
