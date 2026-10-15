<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('subject');
            $table->text('message');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->string('submitter_name')->nullable();
            $table->string('submitter_email')->nullable();
            $table->timestamps();
        });

        Schema::connection('saleprosaas_landlord')->create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->enum('sender_type', ['admin', 'tenant'])->default('tenant');
            $table->text('message');
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('ticket_replies');
        Schema::connection('saleprosaas_landlord')->dropIfExists('support_tickets');
    }
};
