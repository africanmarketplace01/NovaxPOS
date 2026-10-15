<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        DB::connection('saleprosaas_landlord')->table('saas_settings')->insertOrIgnore([
            'key'        => 'site_font',
            'value'      => 'Inter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::connection('saleprosaas_landlord')->table('saas_settings')
            ->where('key', 'site_font')->delete();
    }
};
