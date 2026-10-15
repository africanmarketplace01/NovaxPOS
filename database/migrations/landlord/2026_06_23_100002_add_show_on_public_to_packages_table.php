<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->table('packages', function (Blueprint $table) {
            if (!Schema::connection('saleprosaas_landlord')->hasColumn('packages', 'show_on_public')) {
                // Default true so all existing plans remain visible on public pages
                $table->boolean('show_on_public')->default(true)->after('badge');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->table('packages', function (Blueprint $table) {
            $table->dropColumn('show_on_public');
        });
    }
};
