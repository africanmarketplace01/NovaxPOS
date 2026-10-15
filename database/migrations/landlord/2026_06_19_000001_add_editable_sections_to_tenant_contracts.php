<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEditableSectionsToTenantContracts extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $conn = $this->connection;
        $cols = [
            'purpose_text', 'software_description', 'system_name',
            'sla_uptime', 'sla_critical_hours', 'sla_bugfix_days',
            'sla_maintenance_notice_hours', 'sla_support_hours', 'sla_backup_days',
            'termination_notice_days', 'governing_law',
        ];

        // Skip entirely if all columns already exist
        $existing = array_filter($cols, fn($c) => Schema::connection($conn)->hasColumn('tenant_contracts', $c));
        if (count($existing) === count($cols)) {
            return;
        }

        Schema::connection($conn)->table('tenant_contracts', function (Blueprint $table) use ($conn) {
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'purpose_text'))
                $table->text('purpose_text')->nullable()->after('notes');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'software_description'))
                $table->text('software_description')->nullable()->after('purpose_text');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'system_name'))
                $table->string('system_name', 100)->default('NovaxPOS')->after('software_description');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'sla_uptime'))
                $table->string('sla_uptime', 20)->default('99.5%')->after('system_name');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'sla_critical_hours'))
                $table->smallInteger('sla_critical_hours')->default(48)->after('sla_uptime');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'sla_bugfix_days'))
                $table->smallInteger('sla_bugfix_days')->default(5)->after('sla_critical_hours');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'sla_maintenance_notice_hours'))
                $table->smallInteger('sla_maintenance_notice_hours')->default(24)->after('sla_bugfix_days');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'sla_support_hours'))
                $table->string('sla_support_hours', 100)->default('Monday–Friday, 8 AM–6 PM (Liberia time)')->after('sla_maintenance_notice_hours');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'sla_backup_days'))
                $table->smallInteger('sla_backup_days')->default(30)->after('sla_support_hours');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'termination_notice_days'))
                $table->smallInteger('termination_notice_days')->default(30)->after('sla_backup_days');
            if (!Schema::connection($conn)->hasColumn('tenant_contracts', 'governing_law'))
                $table->string('governing_law', 100)->default('Republic of Liberia')->after('termination_notice_days');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('tenant_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'purpose_text', 'software_description', 'system_name',
                'sla_uptime', 'sla_critical_hours', 'sla_bugfix_days',
                'sla_maintenance_notice_hours', 'sla_support_hours', 'sla_backup_days',
                'termination_notice_days', 'governing_law',
            ]);
        });
    }
}
