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
            if (!Schema::connection('saleprosaas_landlord')->hasColumn('packages', 'price_onetime')) {
                $table->decimal('price_onetime', 10, 2)->nullable()->after('price_yearly');
            }
            if (!Schema::connection('saleprosaas_landlord')->hasColumn('packages', 'allow_paid_trial')) {
                $table->boolean('allow_paid_trial')->default(false)->after('trial_days');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('saleprosaas_landlord')->table('packages', function (Blueprint $table) {
            $table->dropColumn(['price_onetime', 'allow_paid_trial']);
        });
    }
};
