<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landlord DB migration: configurable platform processing fees.
 *
 * Fee precedence (highest wins):
 *   1. Per-tenant override (scope=tenant, scope_id=tenant_id)
 *   2. Per-category override (scope=category, scope_id=category_slug)
 *   3. Global default (scope=global, scope_id=null)
 *
 * Each row can set a percent_fee, flat_fee, or both — they stack: total_fee = subtotal * percent + flat.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('marketplace_fees', function (Blueprint $table) {
            $table->id();

            $table->enum('scope', ['global', 'tenant', 'category'])->default('global')->index();
            $table->string('scope_id', 100)->nullable()->index(); // tenant_id or category slug

            $table->decimal('percent_fee', 5, 2)->default(0);  // e.g. 5.00 = 5%
            $table->decimal('flat_fee', 10, 2)->default(0);    // e.g. 0.25 per order

            $table->string('label', 80)->nullable();            // e.g. "Platform fee"
            $table->boolean('is_active')->default(true);

            $table->string('created_by', 100)->nullable();      // superadmin email
            $table->timestamps();

            $table->unique(['scope', 'scope_id']);
        });

        // Seed the global default row
        DB::connection('saleprosaas_landlord')->table('marketplace_fees')->insert([
            'scope'       => 'global',
            'scope_id'    => null,
            'percent_fee' => 5.00,
            'flat_fee'    => 0.00,
            'label'       => 'Platform processing fee',
            'is_active'   => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('marketplace_fees');
    }
};
