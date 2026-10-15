<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketplace customer accounts + the product-sourcing ("I'm looking for…")
 * request system, including vendor response threads and live chat messages.
 * All tables live on the central/landlord connection.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $c = Schema::connection('saleprosaas_landlord');

        // ── Customer accounts ──────────────────────────────────────────────
        if (!$c->hasTable('marketplace_customers')) {
            $c->create('marketplace_customers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('country')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Link existing/guest orders to an account (by id or email)
        if ($c->hasTable('marketplace_orders') && !$c->hasColumn('marketplace_orders', 'customer_id')) {
            $c->table('marketplace_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id')->index();
            });
        }

        // ── Sourcing requests ("looking for a product/part") ───────────────
        if (!$c->hasTable('sourcing_requests')) {
            $c->create('sourcing_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->string('title');
                $table->text('description');
                $table->string('category')->nullable();
                $table->string('brand')->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('budget_min', 12, 2)->nullable();
                $table->decimal('budget_max', 12, 2)->nullable();
                $table->string('currency', 10)->nullable();
                $table->string('location')->nullable();
                $table->string('image_path')->nullable();
                $table->string('video_path')->nullable();
                $table->enum('status', ['open', 'closed', 'fulfilled'])->default('open')->index();
                $table->unsignedInteger('responses_count')->default(0);
                $table->timestamps();
            });
        }

        // ── Vendor response threads (one per vendor per request) ───────────
        if (!$c->hasTable('sourcing_responses')) {
            $c->create('sourcing_responses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('request_id')->index();
                $table->string('tenant_id')->index();          // vendor tenant id
                $table->string('tenant_name')->nullable();
                $table->unsignedBigInteger('listing_id')->nullable(); // referenced product
                $table->text('message')->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->string('currency', 10)->nullable();
                $table->timestamp('customer_last_read_at')->nullable();
                $table->timestamp('vendor_last_read_at')->nullable();
                $table->timestamps();

                $table->unique(['request_id', 'tenant_id']);
            });
        }

        // ── Chat messages within a thread ──────────────────────────────────
        if (!$c->hasTable('sourcing_messages')) {
            $c->create('sourcing_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('response_id')->index(); // the thread
                $table->unsignedBigInteger('request_id')->index();
                $table->enum('sender_type', ['customer', 'vendor']);
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('tenant_id')->nullable();
                $table->string('sender_name')->nullable();
                $table->text('body')->nullable();
                $table->unsignedBigInteger('listing_id')->nullable(); // "order this" reference
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $c = Schema::connection('saleprosaas_landlord');
        $c->dropIfExists('sourcing_messages');
        $c->dropIfExists('sourcing_responses');
        $c->dropIfExists('sourcing_requests');
        if ($c->hasColumn('marketplace_orders', 'customer_id')) {
            $c->table('marketplace_orders', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }
        $c->dropIfExists('marketplace_customers');
    }
};
