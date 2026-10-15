<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landlord DB migration: delivery agencies that can be selected at checkout.
 * Agencies self-register and superadmin must approve before they go live.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('delivery_agencies', function (Blueprint $table) {
            $table->id();

            // Agency identity
            $table->string('name', 120);
            $table->string('slug', 130)->unique();
            $table->string('contact_name', 100)->nullable();
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('address', 300)->nullable();
            $table->string('logo', 255)->nullable();         // stored filename

            // Service details
            $table->text('description')->nullable();
            $table->string('coverage_areas', 1000)->nullable(); // comma-sep list of cities/regions
            $table->decimal('base_rate', 10, 2)->default(0);   // base delivery fee
            $table->decimal('per_km_rate', 10, 2)->default(0); // additional per km (optional)
            $table->string('currency', 10)->default('USD');
            $table->integer('estimated_days_min')->default(1);
            $table->integer('estimated_days_max')->default(3);

            // Approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Auth — agencies log in to manage their deliveries
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('delivery_agencies');
    }
};
