<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blade @can / @canany check permissions on the USER, which Spatie resolves
 * through the model_has_roles pivot. This app assigns roles via users.role_id,
 * so tenants whose pivot is empty fail every user-level gate. Backfill the
 * pivot from users.role_id. Idempotent (insertOrIgnore + existence checks).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_has_roles') || ! Schema::hasTable('users')) {
            return;
        }

        $users = DB::table('users')
            ->whereNotNull('role_id')
            ->where('is_deleted', 0)
            ->get(['id', 'role_id']);

        foreach ($users as $user) {
            // Skip role_ids that don't exist in roles (FK would reject them)
            if (! DB::table('roles')->where('id', $user->role_id)->exists()) {
                continue;
            }
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id'    => $user->role_id,
                'model_type' => \App\Models\User::class,
                'model_id'   => $user->id,
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally left empty — backfilled data is harmless to keep.
    }
};
