<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API tokens for the marketplace mobile app (customer side). Self-contained
 * Bearer-token auth so it doesn't interfere with the tenant Sanctum setup.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $c = Schema::connection('saleprosaas_landlord');
        if (!$c->hasTable('marketplace_customer_tokens')) {
            $c->create('marketplace_customer_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('name')->default('mobile');
                $table->string('token', 64)->unique();       // sha256 hash of the plaintext token
                $table->string('device')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('marketplace_customer_tokens');
    }
};
