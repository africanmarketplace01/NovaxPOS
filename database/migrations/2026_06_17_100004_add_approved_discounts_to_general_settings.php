<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'approved_discount_percentages')) {
                // JSON array of allowed discount percentages, e.g. [10,20,30,40]
                // NULL means no restriction (free entry)
                $table->text('approved_discount_percentages')->nullable()->after('last_inventory_reset_at');
            }
            if (!Schema::hasColumn('general_settings', 'enforce_approved_discounts')) {
                $table->boolean('enforce_approved_discounts')->default(false)->after('approved_discount_percentages');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['approved_discount_percentages', 'enforce_approved_discounts']);
        });
    }
};
