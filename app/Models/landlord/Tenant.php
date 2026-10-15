<?php

namespace App\Models\landlord;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\landlord\TenantSupport;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    /** subscription_type value for commission-only marketplace sellers. */
    public const MARKETPLACE_SUBSCRIPTION = 'marketplace';

    use HasDatabase, HasDomains;

    /**
     * Custom columns stored in the tenants table (not in the data JSON column).
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'logo',
            'email',
            'phone_number',
            'package_id',
            'subscription_type',
            'expiry_date',
            'status',
            'ecommerce_enabled',
            // Marketplace-seller fee hold (listings hidden while a fee invoice is overdue).
            'marketplace_hold',
            'marketplace_hold_reason',
            'marketplace_hold_at',
        ];
    }

    /**
     * Returns the public URL for the tenant logo, or null if none uploaded.
     * Checks storage/app/landlord-logos/ first (primary), then the legacy public path.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) return null;

        // Primary: storage/app/landlord-logos/ served via the landlord.logo route
        if (file_exists(storage_path('app/landlord-logos/' . $this->logo))) {
            return route('landlord.logo', $this->logo);
        }

        // Legacy fallback: public/landlord/images/tenant-logos/
        if (file_exists(public_path('landlord/images/tenant-logos/' . $this->logo))) {
            return asset('landlord/images/tenant-logos/' . $this->logo);
        }

        return null;
    }

    /**
     * Returns two-letter initials from the company name for avatar fallback.
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->company_name ?? $this->id));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->company_name ?? $this->id, 0, 2));
    }

    protected $casts = [
        'expiry_date' => 'date',
        'status'      => 'boolean',
        'marketplace_hold'    => 'boolean',
        'marketplace_hold_at' => 'datetime',
    ];

    /* ── Relationships ─────────────────────────────────────────────────────── */

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class, 'tenant_id', 'id');
    }

    public function supports(): HasMany
    {
        return $this->hasMany(TenantSupport::class, 'tenant_id', 'id');
    }

    public function activeSupport(): ?TenantSupport
    {
        return $this->supports()
            ->where('status', 'active')
            ->where('ends_at', '>=', now()->toDateString())
            ->orderBy('ends_at', 'desc')
            ->first();
    }

    /* ── Helpers ───────────────────────────────────────────────────────────── */

    public function isActive(): bool
    {
        return (bool) $this->status;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /** Commission-only marketplace seller (no subscription, never expires). */
    public function isMarketplaceSeller(): bool
    {
        return $this->subscription_type === self::MARKETPLACE_SUBSCRIPTION;
    }

    public function getDatabaseName(): string
    {
        return config('tenancy.database.prefix') . $this->getTenantKey();
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
}
