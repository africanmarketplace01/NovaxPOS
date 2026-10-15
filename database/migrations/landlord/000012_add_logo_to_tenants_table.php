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
        Schema::connection('saleprosaas_landlord')->table('tenants', function (Blueprint $table) {
            if (!Schema::connection('saleprosaas_landlord')->hasColumn('tenants', 'logo')) {
                $table->string('logo')->nullable()->after('company_name');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->table('tenants', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};
