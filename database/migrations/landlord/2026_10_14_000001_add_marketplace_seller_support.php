<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Landlord DB: marketplace-only sellers.
 *
 * A marketplace seller is a merchant with no subscription. They get a store
 * trimmed to Products + Online store, never expire, and pay the platform a
 * commission on each marketplace order instead of a package price.
 *
 *  - packages.is_marketplace_seller   marks the one free "Marketplace Seller" plan
 *  - tenants.marketplace_hold*        hides a seller's listings while a fee
 *                                     invoice is overdue (see marketplace:enforce-fee-holds)
 *  - marketplace_orders.seller_commission / fee_breakdown
 *                                     merchant-paid commission and the per-vendor
 *                                     fee split frozen at order time
 *  - platform_fee_requests.due_date   when a fee invoice becomes overdue
 *
 * Every step is guarded: older installs are missing some marketplace tables.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $schema = Schema::connection('saleprosaas_landlord');
        $db = DB::connection('saleprosaas_landlord');

        if ($schema->hasTable('packages') && ! $schema->hasColumn('packages', 'is_marketplace_seller')) {
            $schema->table('packages', function (Blueprint $table) {
                $table->boolean('is_marketplace_seller')->default(false)->after('show_on_public');
            });
        }

        if ($schema->hasTable('tenants') && ! $schema->hasColumn('tenants', 'marketplace_hold')) {
            $schema->table('tenants', function (Blueprint $table) {
                $table->boolean('marketplace_hold')->default(false)->after('ecommerce_enabled');
                $table->string('marketplace_hold_reason', 255)->nullable()->after('marketplace_hold');
                $table->timestamp('marketplace_hold_at')->nullable()->after('marketplace_hold_reason');
            });
        }

        if ($schema->hasTable('marketplace_orders') && ! $schema->hasColumn('marketplace_orders', 'seller_commission')) {
            $schema->table('marketplace_orders', function (Blueprint $table) {
                $table->decimal('seller_commission', 12, 2)->default(0)->after('platform_fee');
                $table->json('fee_breakdown')->nullable()->after('seller_commission');
            });
        }

        if ($schema->hasTable('platform_fee_requests') && ! $schema->hasColumn('platform_fee_requests', 'due_date')) {
            $schema->table('platform_fee_requests', function (Blueprint $table) {
                $table->date('due_date')->nullable()->after('sent_at');
            });
        }

        // The free plan marketplace sellers are created on. Hidden from the
        // normal pricing grid; the "Sell on the marketplace" page uses it.
        if ($schema->hasTable('packages') && ! $db->table('packages')->where('is_marketplace_seller', true)->exists()) {
            $db->table('packages')->insert([
                'name'                  => 'Marketplace Seller',
                'description'           => 'List your products on the marketplace for free. Pay only a small commission when you sell.',
                'price_monthly'         => 0,
                'price_yearly'          => 0,
                'price_onetime'         => null,
                'features'              => json_encode(['product_and_categories', 'online_store']),
                'product_limit'         => 200,
                'user_limit'            => 2,
                'warehouse_limit'       => 1,
                'is_free_trial'         => false,
                'trial_days'            => 0,
                'allow_paid_trial'      => false,
                'is_active'             => true,
                'sort_order'            => 99,
                'badge'                 => null,
                'show_on_public'        => false,
                'is_marketplace_seller' => true,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        }

        // Seller commission defaults to the current global marketplace fee.
        if ($schema->hasTable('saas_settings')) {
            $global = $schema->hasTable('marketplace_fees')
                ? $db->table('marketplace_fees')->where('scope', 'global')->first()
                : null;
            $defaults = [
                'seller_commission_percent' => $global->percent_fee ?? 5,
                'seller_commission_flat'    => $global->flat_fee ?? 0,
                'seller_fee_due_days'       => 7,
            ];
            foreach ($defaults as $key => $value) {
                if (! $db->table('saas_settings')->where('key', $key)->exists()) {
                    $db->table('saas_settings')->insert([
                        'key' => $key, 'value' => (string) $value, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('saleprosaas_landlord');
        $db = DB::connection('saleprosaas_landlord');

        if ($schema->hasColumn('packages', 'is_marketplace_seller')) {
            $db->table('packages')->where('is_marketplace_seller', true)->whereNotExists(function ($q) {
                $q->from('tenants')->whereColumn('tenants.package_id', 'packages.id');
            })->delete();
            $schema->table('packages', fn (Blueprint $t) => $t->dropColumn('is_marketplace_seller'));
        }
        if ($schema->hasColumn('tenants', 'marketplace_hold')) {
            $schema->table('tenants', fn (Blueprint $t) => $t->dropColumn(['marketplace_hold', 'marketplace_hold_reason', 'marketplace_hold_at']));
        }
        if ($schema->hasTable('marketplace_orders') && $schema->hasColumn('marketplace_orders', 'seller_commission')) {
            $schema->table('marketplace_orders', fn (Blueprint $t) => $t->dropColumn(['seller_commission', 'fee_breakdown']));
        }
        if ($schema->hasTable('platform_fee_requests') && $schema->hasColumn('platform_fee_requests', 'due_date')) {
            $schema->table('platform_fee_requests', fn (Blueprint $t) => $t->dropColumn('due_date'));
        }
        if ($schema->hasTable('saas_settings')) {
            $db->table('saas_settings')->whereIn('key', ['seller_commission_percent', 'seller_commission_flat', 'seller_fee_due_days'])->delete();
        }
    }
};
