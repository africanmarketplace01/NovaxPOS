<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert Liberian Dollar (LRD) if it does not already exist
        $exists = DB::table('currencies')->where('code', 'LRD')->exists();
        if (!$exists) {
            DB::table('currencies')->insert([
                'name'          => 'Liberian Dollar',
                'code'          => 'LRD',
                'symbol'        => 'L$',
                'exchange_rate' => 192.50, // approximate LRD per 1 USD — update via Currency settings as needed
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('currencies')->where('code', 'LRD')->delete();
    }
};
