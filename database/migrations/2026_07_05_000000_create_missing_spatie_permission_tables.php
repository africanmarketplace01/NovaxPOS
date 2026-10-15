<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original 2018 permission migration commented out model_has_permissions
 * and model_has_roles, but Spatie's HasRoles trait queries them at runtime
 * (direct-permission checks happen before role checks). They can be empty —
 * they just have to exist. Guarded with hasTable so tenants that already
 * have them (e.g. created from a template DB) are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->unsignedInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign('permission_id')
                    ->references('id')->on('permissions')
                    ->onDelete('cascade');

                $table->primary(['permission_id', 'model_id', 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->unsignedInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign('role_id')
                    ->references('id')->on('roles')
                    ->onDelete('cascade');

                $table->primary(['role_id', 'model_id', 'model_type'],
                    'model_has_roles_role_model_type_primary');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
    }
};
