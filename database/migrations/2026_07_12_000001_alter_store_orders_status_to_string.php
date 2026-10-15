<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The store_orders.status column was originally an ENUM limited to
 * ('pending','processing','completed','cancelled'). The vendor app and the
 * order flow also use 'shipped' and 'delivered', which MySQL truncated.
 * Widen the column to a VARCHAR so all statuses are accepted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_orders')) {
            return;
        }
        // Raw ALTER — works whether the column is currently an ENUM or a string.
        DB::statement("ALTER TABLE `store_orders` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('store_orders')) {
            return;
        }
        DB::statement("ALTER TABLE `store_orders` MODIFY `status` ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
