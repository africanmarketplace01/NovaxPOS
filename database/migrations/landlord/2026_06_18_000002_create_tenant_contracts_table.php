<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        if (Schema::connection('saleprosaas_landlord')->hasTable('tenant_contracts')) {
            return;
        }
        Schema::connection('saleprosaas_landlord')->create('tenant_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->enum('document_type', ['full_contract', 'sla_only', 'sow_only'])->default('full_contract');
            $table->date('effective_date');
            $table->decimal('contract_amount', 10, 2)->nullable();
            $table->unsignedTinyInteger('deposit_percent')->default(80);
            $table->unsignedTinyInteger('balance_percent')->default(20);
            $table->unsignedSmallInteger('hosting_months')->default(12);
            $table->unsignedSmallInteger('support_months')->default(3);
            $table->unsignedSmallInteger('warranty_days')->default(90);
            $table->unsignedSmallInteger('timeline_days')->default(90);
            $table->boolean('include_scope')->default(true);
            $table->json('scope_items')->nullable();        // array of scope line items
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();        // storage path to generated PDF
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_to_email')->nullable();
            $table->string('created_by')->nullable();      // superadmin email
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('tenant_contracts');
    }
};
