<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant migration — adds sidebar_pos and sidebar_online_store to the
 * permissions table and grants them to the owner role (role_id = 1).
 *
 * These permissions are used by the plan-based sidebar gating system:
 *   - sidebar_pos          → controls POS Terminal visibility
 *   - sidebar_online_store → controls Online Store link visibility
 *
 * changePermission() / createTenant() in TenantInfo automatically strips or
 * restores these permissions when a tenant's plan changes, because they are
 * now listed in TenantInfo::features() permission_names.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return; // non-tenant / landlord context — skip
        }

        $newPermissions = [
            'sidebar_pos',
            'sidebar_online_store',
        ];

        foreach ($newPermissions as $name) {
            // Insert permission if it doesn't already exist
            $exists = DB::table('permissions')->where('name', $name)->exists();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name'       => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Grant to the owner/admin role (id 1) — but ONLY if that role exists,
            // otherwise the role_has_permissions FK constraint fails on tenants
            // whose roles table isn't fully seeded. Fall back to the first role.
            $permId = DB::table('permissions')->where('name', $name)->value('id');
            if ($permId && Schema::hasTable('roles')) {
                $roleId = DB::table('roles')->where('id', 1)->exists()
                    ? 1
                    : DB::table('roles')->orderBy('id')->value('id'); // first available role, or null

                if ($roleId) {
                    $alreadyAssigned = DB::table('role_has_permissions')
                        ->where('permission_id', $permId)
                        ->where('role_id', $roleId)
                        ->exists();

                    if (!$alreadyAssigned) {
                        DB::table('role_has_permissions')->insert([
                            'permission_id' => $permId,
                            'role_id'       => $roleId,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $names = ['sidebar_pos', 'sidebar_online_store'];

        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};
