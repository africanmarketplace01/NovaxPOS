<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Season / limited-time promotion applied to ALL plans while active.
 *
 * A single promotion can be "current" at any time (the most recent active one
 * whose window contains now()). The landing page shows discounted prices and a
 * pop-up modal for as long as the promotion window has not expired.
 */
class PlanPromotion extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'plan_promotions';

    protected $fillable = [
        'title', 'badge_text', 'description', 'discount_type', 'discount_value',
        'starts_at', 'ends_at', 'show_modal', 'is_active',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'show_modal'     => 'boolean',
        'is_active'      => 'boolean',
    ];

    /** In-request cache so multiple calls don't re-query. */
    protected static ?PlanPromotion $currentCache = null;
    protected static bool $currentResolved = false;

    /* ── Scopes ────────────────────────────────────────────────────────────── */

    public function scopeLive($query)
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
                     ->where('starts_at', '<=', $now)
                     ->where('ends_at', '>=', $now);
    }

    /* ── Resolver ──────────────────────────────────────────────────────────── */

    /** The single active promotion right now (or null). Safe if table absent. */
    public static function current(): ?PlanPromotion
    {
        if (static::$currentResolved) {
            return static::$currentCache;
        }
        static::$currentResolved = true;

        try {
            static::$currentCache = static::query()->live()->latest('starts_at')->first();
        } catch (\Throwable $e) {
            static::$currentCache = null;
        }
        return static::$currentCache;
    }

    /* ── Helpers ───────────────────────────────────────────────────────────── */

    public function isLive(): bool
    {
        $now = Carbon::now();
        return $this->is_active
            && $this->starts_at && $this->ends_at
            && $this->starts_at->lte($now) && $this->ends_at->gte($now);
    }

    /** Apply this promotion's discount to a raw price. */
    public function applyTo(float $price): float
    {
        if ($price <= 0) {
            return $price;
        }
        $discounted = $this->discount_type === 'flat'
            ? $price - $this->discount_value
            : $price - ($price * $this->discount_value / 100);

        return round(max(0, $discounted), 2);
    }

    /** Discount a price using the current live promotion, if any. */
    public static function discount(float $price): float
    {
        $promo = static::current();
        return $promo ? $promo->applyTo($price) : $price;
    }

    /** Human-readable label, e.g. "25% OFF" or "$10 OFF". */
    public function label(): string
    {
        if ($this->badge_text) {
            return $this->badge_text;
        }
        return $this->discount_type === 'flat'
            ? '$' . rtrim(rtrim(number_format($this->discount_value, 2), '0'), '.') . ' OFF'
            : rtrim(rtrim(number_format($this->discount_value, 2), '0'), '.') . '% OFF';
    }

    public function endsAtIso(): string
    {
        return optional($this->ends_at)->toIso8601String() ?? '';
    }
}
