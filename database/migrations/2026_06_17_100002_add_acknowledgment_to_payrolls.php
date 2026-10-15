<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable()->after('amount_array');
            }
            if (!Schema::hasColumn('payrolls', 'acknowledged_note')) {
                $table->string('acknowledged_note', 500)->nullable()->after('acknowledged_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['acknowledged_at', 'acknowledged_note']);
        });
    }
};
