<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TenantSupport extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'tenant_supports';

    protected $fillable = [
        'tenant_id',
        'starts_at',
        'ends_at',
        'amount',
        'currency',
        'payment_ref',
        'status',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at'   => 'date',
        'amount'    => 'float',
    ];

    /* ── Relationships ─────────────────────────────────────────────────────── */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /* ── Scopes ────────────────────────────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('ends_at', '>=', now()->toDateString());
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /* ── Helpers ───────────────────────────────────────────────────────────── */

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at->isFuture();
    }

    public function daysRemaining(): int
    {
        return max(0, (int) now()->diffInDays($this->ends_at, false));
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        return $this->isActive() && $this->daysRemaining() <= $withinDays;
    }

    /**
     * Auto-expire records where ends_at has passed but status is still active.
     * Call from a scheduled command or on read.
     */
    public static function syncExpiredStatuses(): void
    {
        static::where('status', 'active')
              ->where('ends_at', '<', now()->toDateString())
              ->update(['status' => 'expired']);
    }
}
