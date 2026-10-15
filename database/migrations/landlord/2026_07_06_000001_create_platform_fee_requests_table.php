<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landlord DB: platform fee requests sent to vendors.
 * Each row = one billing period for one tenant, with the full order breakdown
 * frozen as JSON so the invoice never changes retroactively.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_fee_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('tenant_id', 60)->index();
            $table->string('tenant_name', 150)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('orders_count')->default(0);
            $table->decimal('sales_subtotal', 12, 2)->default(0);
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->json('breakdown')->nullable();          // per-order lines
            $table->string('status', 20)->default('draft'); // draft | sent | paid | cancelled
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_fee_requests');
    }
};
