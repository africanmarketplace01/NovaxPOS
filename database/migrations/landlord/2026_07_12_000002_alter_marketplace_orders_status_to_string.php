<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * marketplace_orders.status was an ENUM
 * ('pending','processing','shipped','delivered','cancelled') — missing
 * 'completed'. Widen it to VARCHAR so the full fulfilment lifecycle
 * (pending → processing → shipped → delivered → completed / cancelled)
 * is accepted, matching the tenant-side store_orders.status.
 *
 * Runs on the central/landlord connection: php artisan landlord:migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_orders')) {
            return;
        }
        DB::statement("ALTER TABLE `marketplace_orders` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_orders')) {
            return;
        }
        DB::statement("ALTER TABLE `marketplace_orders` MODIFY `status` ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
