<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timeline of status changes for a marketplace order — powers order tracking
 * for customers (dashboard) and admins.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $c = Schema::connection('saleprosaas_landlord');

        if (!$c->hasTable('marketplace_order_events')) {
            $c->create('marketplace_order_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->string('status');            // pending|processing|shipped|delivered|cancelled|paid|note
                $table->string('title');             // human label, e.g. "Order shipped"
                $table->text('note')->nullable();
                $table->string('actor')->nullable(); // who triggered it (customer|vendor|admin|system)
                $table->timestamps();
            });
        }

        // Optional tracking number for shipped orders
        if ($c->hasTable('marketplace_orders') && !$c->hasColumn('marketplace_orders', 'tracking_number')) {
            $c->table('marketplace_orders', function (Blueprint $table) {
                $table->string('tracking_number')->nullable()->after('estimated_delivery');
            });
        }
    }

    public function down(): void
    {
        $c = Schema::connection('saleprosaas_landlord');
        $c->dropIfExists('marketplace_order_events');
        if ($c->hasColumn('marketplace_orders', 'tracking_number')) {
            $c->table('marketplace_orders', function (Blueprint $table) {
                $table->dropColumn('tracking_number');
            });
        }
    }
};
