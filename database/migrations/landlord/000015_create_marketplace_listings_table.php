<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landlord DB migration: denormalized product listings from tenant DBs.
 *
 * Tenants push their products here when they enable marketplace listing.
 * This avoids querying every tenant DB on every marketplace page load.
 * The tenant side calls a sync endpoint whenever product data changes.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('marketplace_listings', function (Blueprint $table) {
            $table->id();

            // Source reference
            $table->string('tenant_id', 100)->index();
            $table->unsignedBigInteger('product_id');           // ID in tenant DB
            $table->string('tenant_name', 150)->nullable();
            $table->string('tenant_store_name', 150)->nullable();

            // Product data (synced from tenant)
            $table->string('name', 255);
            $table->string('slug', 280)->nullable();
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('brand', 100)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('promo_price', 12, 2)->nullable();  // promotion price if active
            $table->string('currency', 10)->default('USD');
            $table->integer('stock_qty')->default(0);
            $table->boolean('in_stock')->default(true);

            // Visibility
            $table->boolean('is_active')->default(true);        // tenant can deactivate
            $table->boolean('approved')->default(true);         // superadmin can hide
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'product_id']);
            $table->index(['is_active', 'approved', 'in_stock']);
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('marketplace_listings');
    }
};
