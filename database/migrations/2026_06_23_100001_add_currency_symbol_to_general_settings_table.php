<?php

use Illuminate\Database\Migrations\Migration;

// This migration was superseded — currency_symbol does not belong in general_settings.
// The app stores the currency ID in general_settings.currency and reads the symbol
// from the currencies table (currencies.symbol). No schema change needed.
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
