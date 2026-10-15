<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Assign a store/marketplace order to a delivery agent (rider). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_orders') && ! Schema::hasColumn('store_orders', 'delivery_agent_id')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('delivery_agent_id')->nullable()->after('delivery_agency_name')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('store_orders') && Schema::hasColumn('store_orders', 'delivery_agent_id')) {
            Schema::table('store_orders', fn (Blueprint $t) => $t->dropColumn('delivery_agent_id'));
        }
    }
};
