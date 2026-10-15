<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sanctum personal access tokens, stored on the central platform DB so vendor
 * mobile-app auth is consistent across tenancy switches.
 * See App\Models\PersonalAccessToken.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        if (Schema::connection('saleprosaas_landlord')->hasTable('personal_access_tokens')) {
            return;
        }

        Schema::connection('saleprosaas_landlord')->create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('personal_access_tokens');
    }
};
