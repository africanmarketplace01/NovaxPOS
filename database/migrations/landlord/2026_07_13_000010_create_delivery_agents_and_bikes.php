<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery agents (riders) and their bikes, owned by a delivery agency.
 * Orders can be assigned to an agent for fulfilment.
 */
return new class extends Migration
{
    protected $connection = 'saleprosaas_landlord';

    public function up(): void
    {
        $conn = Schema::connection('saleprosaas_landlord');

        if (! $conn->hasTable('delivery_agents')) {
            $conn->create('delivery_agents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id')->index();
                $table->string('name', 120);
                $table->string('phone', 30)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('photo', 255)->nullable();          // stored filename
                $table->string('license_number', 80)->nullable();  // driver's license
                $table->string('national_id', 80)->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('password')->nullable();            // for agent login later
                $table->string('remember_token', 100)->nullable();
                $table->timestamps();
            });
        }

        if (! $conn->hasTable('delivery_bikes')) {
            $conn->create('delivery_bikes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id')->index();
                $table->unsignedBigInteger('agent_id')->nullable()->index(); // assigned rider
                $table->string('make', 80)->nullable();            // e.g. Honda
                $table->string('model', 80)->nullable();           // e.g. CG 125
                $table->string('plate_number', 40);                // license plate
                $table->string('color', 40)->nullable();
                $table->string('year', 8)->nullable();
                $table->string('registration_no', 80)->nullable();
                $table->string('insurance_no', 80)->nullable();
                $table->text('note')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Assign an order to an agent (landlord marketplace orders)
        if ($conn->hasTable('marketplace_orders') && ! $conn->hasColumn('marketplace_orders', 'delivery_agent_id')) {
            $conn->table('marketplace_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('delivery_agent_id')->nullable()->after('delivery_agency_name')->index();
            });
        }
    }

    public function down(): void
    {
        $conn = Schema::connection('saleprosaas_landlord');
        if ($conn->hasTable('marketplace_orders') && $conn->hasColumn('marketplace_orders', 'delivery_agent_id')) {
            $conn->table('marketplace_orders', fn (Blueprint $t) => $t->dropColumn('delivery_agent_id'));
        }
        $conn->dropIfExists('delivery_bikes');
        $conn->dropIfExists('delivery_agents');
    }
};
