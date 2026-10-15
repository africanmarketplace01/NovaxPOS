<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeliveryAgency extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'delivery_agencies';

    protected $fillable = [
        'owner_tenant_id', 'name', 'slug', 'contact_name', 'email', 'phone', 'address', 'logo',
        'description', 'coverage_areas', 'base_rate', 'per_km_rate', 'currency',
        'estimated_days_min', 'estimated_days_max',
        'status', 'is_active', 'rejection_reason', 'approved_at', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'base_rate'      => 'float',
        'per_km_rate'    => 'float',
        'is_active'      => 'boolean',
        'approved_at'    => 'datetime',
    ];

    public function zones()
    {
        return $this->hasMany(DeliveryZone::class, 'agency_id');
    }

    public function zonePrices()
    {
        return $this->hasMany(DeliveryZonePrice::class, 'agency_id');
    }

    /** Vendor-owned (in-house) agency created by a tenant. */
    public function isVendorOwned(): bool
    {
        return ! empty($this->owner_tenant_id);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Human-readable delivery time estimate, e.g. "1–3 business days"
     */
    public function deliveryEstimate(): string
    {
        if ($this->estimated_days_min === $this->estimated_days_max) {
            return $this->estimated_days_min . ' business day' . ($this->estimated_days_min > 1 ? 's' : '');
        }
        return $this->estimated_days_min . '–' . $this->estimated_days_max . ' business days';
    }

    /**
     * Coverage areas as an array.
     */
    public function coverageList(): array
    {
        if (empty($this->coverage_areas)) return [];
        return array_map('trim', explode(',', $this->coverage_areas));
    }

    /**
     * Auto-generate slug from name.
     */
    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $i    = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = Str::slug($name) . '-' . $i++;
        }
        return $slug;
    }
}
