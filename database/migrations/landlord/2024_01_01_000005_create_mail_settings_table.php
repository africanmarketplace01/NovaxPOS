<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('driver')->default('smtp');
            $table->string('host')->nullable();
            $table->string('port')->nullable();
            $table->string('encryption')->nullable();
            $table->string('user_name')->nullable();
            $table->string('password')->nullable();
            $table->string('sendgrid_api_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('mail_settings');
    }
};
