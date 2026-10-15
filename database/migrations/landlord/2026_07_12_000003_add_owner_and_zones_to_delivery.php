<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery zones + zone-to-zone pricing (landlord DB).
 *
 * - Vendors (tenants) can own their own delivery agencies (in-house delivery)
 *   via delivery_agencies.owner_tenant_id.
 * - Both registered agencies and vendor-owned agencies can define delivery
 *   zones and zone-to-zone pricing, applied at checkout.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $conn = Schema::connection('saleprosaas_landlord');

        $conn->table('delivery_agencies', function (Blueprint $table) {
            if (! Schema::connection('saleprosaas_landlord')->hasColumn('delivery_agencies', 'owner_tenant_id')) {
                $table->string('owner_tenant_id')->nullable()->after('id')->index();
            }
            if (! Schema::connection('saleprosaas_landlord')->hasColumn('delivery_agencies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        if (! $conn->hasTable('delivery_zones')) {
            $conn->create('delivery_zones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id')->index();
                $table->string('name', 120);
                $table->string('note', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! $conn->hasTable('delivery_zone_prices')) {
            $conn->create('delivery_zone_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id')->index();
                $table->unsignedBigInteger('from_zone_id');
                $table->unsignedBigInteger('to_zone_id');
                $table->decimal('price', 10, 2)->default(0);
                $table->integer('est_days_min')->nullable();
                $table->integer('est_days_max')->nullable();
                $table->timestamps();
                $table->unique(['agency_id', 'from_zone_id', 'to_zone_id'], 'zone_price_unique');
            });
        }
    }

    public function down(): void
    {
        $conn = Schema::connection('saleprosaas_landlord');
        $conn->dropIfExists('delivery_zone_prices');
        $conn->dropIfExists('delivery_zones');
        $conn->table('delivery_agencies', function (Blueprint $table) {
            $table->dropColumn(['owner_tenant_id', 'is_active']);
        });
    }
};
