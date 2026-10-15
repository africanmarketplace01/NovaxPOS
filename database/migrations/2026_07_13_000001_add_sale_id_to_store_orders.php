<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a store/marketplace order to the Sale record created automatically when
 * the order is marked "completed" (so completion produces a real sales record
 * and cancellation can reverse it).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_orders') && ! Schema::hasColumn('store_orders', 'sale_id')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->unsignedInteger('sale_id')->nullable()->after('status')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('store_orders') && Schema::hasColumn('store_orders', 'sale_id')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->dropColumn('sale_id');
            });
        }
    }
};
