<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant-DB migration: creates store_settings and store_orders tables.
 * Run with: php artisan tenants:migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Store Settings (one row per tenant) ─────────────────────────────
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('store_name')->nullable();
            $table->text('store_about')->nullable();
            $table->string('store_banner')->nullable();     // relative path or URL
            $table->string('store_logo')->nullable();
            $table->string('store_currency')->nullable();
            $table->boolean('show_out_of_stock')->default(true);
            $table->boolean('allow_orders')->default(true); // false = browse-only
            $table->text('order_note')->nullable();         // shown at checkout
            $table->string('whatsapp_number')->nullable();  // for WhatsApp order CTA
            $table->timestamps();
        });

        // ── Store Orders ────────────────────────────────────────────────────
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->json('items');          // [{product_id, name, qty, price, subtotal}]
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->text('note')->nullable();
            // Order fulfilment status. VARCHAR (not ENUM) so statuses like
            // 'shipped' and 'delivered' can be used without altering the column.
            $table->string('status', 20)->default('pending');
            // Sale record created automatically when the order is completed.
            $table->unsignedInteger('sale_id')->nullable()->index();
            // Assigned delivery agent (rider).
            $table->unsignedBigInteger('delivery_agent_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
        Schema::dropIfExists('store_settings');
    }
};
