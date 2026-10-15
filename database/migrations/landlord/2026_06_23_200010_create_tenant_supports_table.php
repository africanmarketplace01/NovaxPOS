<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('tenant_supports', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('payment_ref')->nullable();
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index(['tenant_id', 'status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('tenant_supports');
    }
};
