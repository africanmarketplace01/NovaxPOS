<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant DB migration: extend store_orders with delivery, payment, and marketplace tracking.
 * Run with: php artisan tenants:migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            // Payment
            $table->string('payment_method', 30)->default('cod')->after('currency');
            $table->string('payment_status', 20)->default('pending')->after('payment_method');
            $table->string('payment_reference', 200)->nullable()->after('payment_status');

            // Delivery
            $table->unsignedBigInteger('delivery_agency_id')->nullable()->after('payment_reference');
            $table->string('delivery_agency_name', 120)->nullable()->after('delivery_agency_id');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('delivery_agency_name');
            $table->string('estimated_delivery', 50)->nullable()->after('delivery_fee');

            // Platform fee (charged by marketplace)
            $table->decimal('platform_fee', 10, 2)->default(0)->after('estimated_delivery');

            // Source: direct (from tenant store) or marketplace (from global marketplace)
            $table->enum('source', ['direct', 'marketplace'])->default('direct')->after('platform_fee');
            $table->string('marketplace_order_number', 40)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method', 'payment_status', 'payment_reference',
                'delivery_agency_id', 'delivery_agency_name', 'delivery_fee', 'estimated_delivery',
                'platform_fee', 'source', 'marketplace_order_number',
            ]);
        });
    }
};
