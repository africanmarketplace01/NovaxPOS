<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'commission_percent')) {
                // Per-item commission percentage paid to the selling employee
                $table->decimal('commission_percent', 5, 2)->nullable()->after('profit_margin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });
    }
};
