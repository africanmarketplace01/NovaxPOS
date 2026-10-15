<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return; // landlord / non-tenant context
        }

        // Add sidebar_api_token permission if it doesn't already exist
        $permId = DB::table('permissions')->where('name', 'sidebar_api_token')->value('id');
        if (! $permId) {
            $permId = DB::table('permissions')->insertGetId([
                'name'       => 'sidebar_api_token',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Grant to the owner/admin role (id 1) — ONLY if it exists, else the first
        // role, else skip. Migrations run on an empty tenant DB before seeding, so
        // role 1 may not exist yet and a hard insert would break the FK constraint.
        if ($permId && Schema::hasTable('roles')) {
            $roleId = DB::table('roles')->where('id', 1)->exists()
                ? 1
                : DB::table('roles')->orderBy('id')->value('id');

            if ($roleId) {
                $rolePermExists = DB::table('role_has_permissions')
                    ->where('permission_id', $permId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (! $rolePermExists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permId,
                        'role_id'       => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $perm = DB::table('permissions')->where('name', 'sidebar_api_token')->first();
        if ($perm) {
            DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('permissions')->where('id', $perm->id)->delete();
        }
    }
};
