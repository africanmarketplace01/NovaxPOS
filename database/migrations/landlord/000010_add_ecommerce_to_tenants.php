<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->table('tenants', function (Blueprint $table) {
            if (!Schema::connection('saleprosaas_landlord')->hasColumn('tenants', 'ecommerce_enabled')) {
                $table->boolean('ecommerce_enabled')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->table('tenants', function (Blueprint $table) {
            $table->dropColumn('ecommerce_enabled');
        });
    }
};
