<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        Schema::connection('saleprosaas_landlord')->create('saas_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default LinkedPOS theme values
        $defaults = [
            ['key' => 'theme_primary',        'value' => '#0066CC'],
            ['key' => 'theme_primary_light',  'value' => '#00A3FF'],
            ['key' => 'theme_accent',         'value' => '#66FF33'],
            ['key' => 'theme_lime',           'value' => '#8DFF00'],
            ['key' => 'theme_dark',           'value' => '#003366'],
            ['key' => 'theme_cyan',           'value' => '#00D4FF'],
            ['key' => 'site_name',            'value' => 'NovaxPOS'],
            ['key' => 'site_tagline',         'value' => 'The Modern Cloud POS Platform'],
            ['key' => 'support_email',        'value' => 'support@novaxpos.com'],
            ['key' => 'trial_days',           'value' => '14'],
            ['key' => 'site_font',            'value' => 'Inter'],
        ];

        foreach ($defaults as $row) {
            DB::connection('saleprosaas_landlord')->table('saas_settings')->insertOrIgnore(
                array_merge($row, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->dropIfExists('saas_settings');
    }
};
