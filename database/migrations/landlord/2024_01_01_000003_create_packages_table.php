<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->json('features')->nullable();          // array of feature keys
            $table->unsignedInteger('product_limit')->nullable();   // null = unlimited
            $table->unsignedInteger('user_limit')->nullable();
            $table->unsignedInteger('warehouse_limit')->nullable();
            $table->boolean('is_free_trial')->default(false);
            $table->unsignedInteger('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('badge')->nullable();           // "Most Popular"
            $table->timestamps();
        });

        // Add foreign key to tenants table
        Schema::connection('saleprosaas_landlord')->table('tenants', function (Blueprint $table) {
            $table->foreign('package_id')->references('id')->on('packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->table('tenants', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
        });
        Schema::connection('saleprosaas_landlord')->dropIfExists('packages');
    }
};
