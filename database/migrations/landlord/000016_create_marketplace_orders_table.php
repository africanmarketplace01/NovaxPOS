<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landlord DB migration: global marketplace orders.
 *
 * One row per checkout. Items are grouped by tenant inside the items JSON.
 * After checkout, sub-orders are created in each tenant's store_orders table.
 * Platform fees and delivery fees are tracked here for revenue accounting.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();

            // Customer
            $table->string('customer_name', 120);
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 30)->nullable();
            $table->text('customer_address')->nullable();

            // Items — JSON array grouped by tenant:
            // [{ tenant_id, tenant_name, items: [{...}], subtotal }]
            $table->json('items');

            // Financials
            $table->string('currency', 10)->default('USD');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);    // revenue for NovaxPOS
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Delivery
            $table->foreignId('delivery_agency_id')->nullable()->constrained('delivery_agencies')->nullOnDelete();
            $table->string('delivery_agency_name', 120)->nullable(); // snapshot
            $table->string('estimated_delivery', 50)->nullable();

            // Payment
            $table->enum('payment_method', ['stripe', 'paystack', 'bank_transfer', 'mtn', 'orange', 'cod'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_reference', 200)->nullable();

            // Fulfilment status. VARCHAR (not ENUM) so the full lifecycle —
            // pending, processing, shipped, delivered, completed, cancelled —
            // is accepted without altering the column.
            $table->string('status', 20)->default('pending');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['status', 'payment_status']);
            $table->index('customer_email');
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('marketplace_orders');
    }
};
