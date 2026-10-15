<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landlord DB: in-app notifications for marketplace events.
 * recipient_type: superadmin | agency | tenant (tenant rows are an audit copy;
 * tenants also get a row in their own DB's notifications table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_type', 20)->index();   // superadmin | agency | tenant
            $table->string('recipient_id', 60)->nullable()->index(); // agency id / tenant id
            $table->string('order_number', 40)->nullable()->index();
            $table->string('title', 150);
            $table->text('body')->nullable();
            $table->string('link', 255)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_notifications');
    }
};
