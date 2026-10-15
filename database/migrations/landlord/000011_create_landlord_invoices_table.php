<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection(): string
    {
        return 'saleprosaas_landlord';
    }

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('landlord_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 32)->unique();
            $table->string('tenant_id', 64)->nullable()->index();
            $table->string('tenant_name', 200)->nullable();
            $table->string('tenant_email', 200)->nullable();
            $table->string('tenant_company', 200)->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 20)->default('unpaid'); // unpaid, paid, partial, cancelled
            $table->text('notes')->nullable();
            $table->json('line_items')->nullable(); // [{desc, qty, unit_price, amount}]
            $table->string('payment_method', 50)->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('landlord_invoices');
    }
};
