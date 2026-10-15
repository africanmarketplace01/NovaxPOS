<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Run against the LANDLORD database (novaxpos_landlord).
 * Execute with: php artisan migrate --path=database/migrations/landlord --database=saleprosaas_landlord
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();          // subdomain e.g. "acme"
            $table->json('data')->nullable();          // stancl/tenancy internal data
            $table->string('company_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone_number')->nullable();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('subscription_type')->nullable(); // monthly | yearly | free_trial
            $table->date('expiry_date')->nullable();
            $table->boolean('status')->default(true);  // true=active, false=suspended
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('tenants');
    }
};
