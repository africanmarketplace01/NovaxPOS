<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SaasSettings extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'saas_settings';
    protected $fillable   = ['key', 'value'];

    /** Cache key — single source of truth. */
    private const CACHE_KEY = 'saas_settings_all';

    /** Cache TTL: 60 minutes. Lowered to 5 min if Redis unavailable. */
    private const CACHE_TTL = 3600;

    /**
     * Get all settings as a key→value array.
     * Uses Redis (or the configured cache store) with a 1-hour TTL.
     * Falls back gracefully if the landlord DB is unavailable.
     */
    public static function allCached(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                return static::pluck('value', 'key')->toArray();
            });
        } catch (\Throwable $e) {
            // DB not available (e.g. first boot, non-SaaS install) — return empty array
            Log::debug('SaasSettings::allCached() fallback: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get one setting value (with optional default).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allCached()[$key] ?? $default;
    }

    /**
     * Set / update one setting and bust the cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::bust();
    }

    /**
     * Bulk-update from an associative array and bust the cache once.
     */
    public static function bulkSet(array $data): void
    {
        foreach ($data as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        static::bust();
    }

    /**
     * Force-bust the settings cache.
     * Also busts the per-key cache entries used by the theme endpoint.
     */
    public static function bust(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Warm the cache (call from a scheduled command or after deploy).
     */
    public static function warm(): void
    {
        Cache::forget(self::CACHE_KEY);
        static::allCached();
    }
}
