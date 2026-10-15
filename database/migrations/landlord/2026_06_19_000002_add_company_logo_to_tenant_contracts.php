<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyLogoToTenantContracts extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection($this->connection)->table('tenant_contracts', function (Blueprint $table) {
            if (!Schema::connection($this->connection)->hasColumn('tenant_contracts', 'company_logo')) {
                $table->string('company_logo', 255)->nullable()->after('pdf_path');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('tenant_contracts', function (Blueprint $table) {
            $table->dropColumn('company_logo');
        });
    }
}
