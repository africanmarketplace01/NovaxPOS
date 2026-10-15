<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landlord DB: payouts to vendors for marketplace orders the platform collected.
 *
 * Online payments (Stripe, Paystack, bank transfer, MTN, Orange) land in the
 * platform's accounts, so the platform owes each vendor their items' value,
 * minus the commission a marketplace-only seller owes. One vendor_payouts row
 * per vendor per period; vendor_payout_orders records which orders it covered
 * so an order can never be paid out twice (cancelling a payout frees them).
 *
 * Cash-on-delivery orders are the other direction: the vendor collected the
 * cash and is billed through platform_fee_requests instead.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $schema = Schema::connection('saleprosaas_landlord');

        if (! $schema->hasTable('vendor_payouts')) {
            $schema->create('vendor_payouts', function (Blueprint $table) {
                $table->id();
                $table->string('reference', 40)->unique();
                $table->string('tenant_id', 60)->index();
                $table->string('tenant_name', 150)->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->unsignedInteger('orders_count')->default(0);
                $table->decimal('gross_amount', 12, 2)->default(0);      // vendor's item value
                $table->decimal('commission_amount', 12, 2)->default(0); // seller commission deducted
                $table->decimal('net_amount', 12, 2)->default(0);        // amount to send the vendor
                $table->string('currency', 10)->default('USD');
                $table->json('breakdown')->nullable();                    // per-order lines, frozen
                $table->string('status', 20)->default('draft');          // draft | paid | cancelled
                $table->string('payout_method', 40)->nullable();          // bank, mtn, orange, cash…
                $table->string('payout_reference', 120)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('vendor_payout_orders')) {
            $schema->create('vendor_payout_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('vendor_payout_id')->index();
                $table->string('tenant_id', 60);
                $table->unsignedBigInteger('marketplace_order_id');
                $table->timestamps();
                // One payout per vendor share of an order.
                $table->unique(['tenant_id', 'marketplace_order_id']);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('saleprosaas_landlord');
        $schema->dropIfExists('vendor_payout_orders');
        $schema->dropIfExists('vendor_payouts');
    }
};
