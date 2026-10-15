<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_warehouse', function (Blueprint $table) {
            if (!Schema::hasColumn('product_warehouse', 'location_barcode')) {
                // Per-location barcode: overrides the global product code for barcode scanning at a specific warehouse
                $table->string('location_barcode', 100)->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_warehouse', function (Blueprint $table) {
            $table->dropColumn('location_barcode');
        });
    }
};
