<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $keys = [
        // Inventory sidebar labels
        'Product Categories',
        'Units of Measure',
        'Stock Adjustments',
        'Stock Transfers',
        'Stock History',
        'Low Stock Alerts',
        'Purchase Orders',
        'Goods Received',
        'Inventory Valuation',
        'Stock Movement',
        'Expiry Report',
        'Product Performance',
        // Settings sidebar labels
        'General Settings',
        'POS Settings',
        'Customer Groups',
        'Discount Plans',
        'Discounts',
        'Reward Point Settings',
        'HRM Settings',
        'Roles & Permissions',
        'Mail Settings',
        'SMS Settings',
        'SMS Templates',
        'Notification Templates',
        'Send Notifications',
        'Custom Fields',
    ];

    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('translations')) {
            return;
        }

        // Get all distinct locales already in the table (seed for each)
        $locales = DB::table('translations')->distinct()->pluck('locale')->toArray();
        if (empty($locales)) {
            $locales = ['en'];
        }

        // Get existing keys to avoid duplicates
        $existing = DB::table('translations')
            ->whereIn('key', $this->keys)
            ->get(['locale', 'key'])
            ->map(fn($r) => $r->locale . '|' . $r->key)
            ->flip()
            ->toArray();

        $rows = [];
        foreach ($locales as $locale) {
            foreach ($this->keys as $key) {
                if (!isset($existing[$locale . '|' . $key])) {
                    $rows[] = [
                        'locale'     => $locale,
                        'group'      => 'db',
                        'key'        => $key,
                        'value'      => $key,   // English label as default for all locales
                        'created_at' => null,
                        'updated_at' => null,
                    ];
                }
            }
        }

        if (!empty($rows)) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('translations')->insert($chunk);
            }
        }

        // Clear translation cache so changes take effect immediately
        try {
            \Illuminate\Support\Facades\Cache::forget('translations_by_locale');
            \Illuminate\Support\Facades\Cache::flush();
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        DB::table('translations')->whereIn('key', $this->keys)->delete();
    }
};
