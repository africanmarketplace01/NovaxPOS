<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        if (Schema::connection('saleprosaas_landlord')->hasTable('plan_promotions')) {
            return;
        }

        Schema::connection('saleprosaas_landlord')->create('plan_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('title');                        // e.g. "Summer Sale"
            $table->string('badge_text')->nullable();       // small chip, e.g. "-25% OFF"
            $table->text('description')->nullable();         // modal body copy
            $table->enum('discount_type', ['percentage', 'flat'])->default('percentage');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('show_modal')->default(true);    // show pop-up on landing while active
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('plan_promotions');
    }
};
