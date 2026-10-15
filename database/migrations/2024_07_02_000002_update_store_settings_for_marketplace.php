<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant DB migration: add marketplace and payment settings to store_settings.
 * Run with: php artisan tenants:migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            // Marketplace
            $table->boolean('marketplace_listed')->default(false)->after('allow_orders');
            $table->timestamp('marketplace_listed_at')->nullable()->after('marketplace_listed');

            // Payment methods accepted by this store
            $table->boolean('accept_stripe')->default(false)->after('marketplace_listed_at');
            $table->boolean('accept_paystack')->default(false)->after('accept_stripe');
            $table->boolean('accept_bank_transfer')->default(false)->after('accept_paystack');
            $table->boolean('accept_mtn')->default(false)->after('accept_bank_transfer');
            $table->boolean('accept_orange')->default(false)->after('accept_mtn');
            $table->boolean('accept_cod')->default(true)->after('accept_orange');

            // Tax rate applied to store orders
            $table->decimal('tax_rate', 5, 2)->default(0)->after('accept_cod');

            // Social / contact
            $table->string('store_email', 150)->nullable()->after('whatsapp_number');
            $table->string('store_instagram', 200)->nullable()->after('store_email');
            $table->string('store_facebook', 200)->nullable()->after('store_instagram');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'marketplace_listed', 'marketplace_listed_at',
                'accept_stripe', 'accept_paystack', 'accept_bank_transfer',
                'accept_mtn', 'accept_orange', 'accept_cod',
                'tax_rate', 'store_email', 'store_instagram', 'store_facebook',
            ]);
        });
    }
};
