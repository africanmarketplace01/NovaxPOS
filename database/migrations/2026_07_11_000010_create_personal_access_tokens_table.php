<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sanctum personal access tokens.
 *
 * Lives in database/migrations so it runs inside every tenant DB during
 * provisioning (`tenants:migrate`), colocating the token with its `users` so
 * Sanctum authenticates the vendor app in tenant context.
 *
 * Uses `CREATE TABLE IF NOT EXISTS` on the migration's active connection so it
 * is idempotent and connection-safe: it CREATES the table where it's missing
 * (each tenant DB) and is a harmless no-op where it already exists (the central
 * DB). Crucially it never early-returns, so it can't be recorded as "done"
 * without actually creating the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
              `id` bigint unsigned NOT NULL AUTO_INCREMENT,
              `tokenable_type` varchar(255) NOT NULL,
              `tokenable_id` bigint unsigned NOT NULL,
              `name` varchar(255) NOT NULL,
              `token` varchar(64) NOT NULL,
              `abilities` text NULL,
              `last_used_at` timestamp NULL DEFAULT NULL,
              `expires_at` timestamp NULL DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT NULL,
              `updated_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
              KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(): void
    {
        // No-op: the table is shared across installs; don't drop on rollback.
    }
};
