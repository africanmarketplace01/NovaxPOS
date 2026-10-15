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
        if (Schema::connection('saleprosaas_landlord')->hasTable('landlord_testimonials')) {
            return;
        }
        Schema::connection('saleprosaas_landlord')->create('landlord_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_role')->nullable();
            $table->string('author_avatar')->nullable();   // uploaded image filename
            $table->string('author_initials', 4)->nullable(); // fallback e.g. "JK"
            $table->tinyInteger('stars')->default(5);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('landlord_testimonials');
    }
};
