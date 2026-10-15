<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use App\Models\landlord\Tenant;

return [

    'tenant_model' => Tenant::class,

    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Tenancy bootstrappers
    |--------------------------------------------------------------------------
    | These bootstrappers run when tenancy is initialized. They're responsible
    | for switching the application into tenant-mode.
    */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database tenancy config
    |--------------------------------------------------------------------------
    | Each tenant gets its own database. The name is built from:
    |   prefix + tenant_id + suffix
    */
    'database' => [
        // When LANDLORD_DB is set (SaaS mode), the central/landlord tables
        // (tenants, domains, packages…) live in the saleprosaas_landlord DB.
        // Without LANDLORD_DB the standard mysql connection is used.
        'central_connection' => env('LANDLORD_DB') ? 'saleprosaas_landlord' : env('DB_CONNECTION', 'mysql'),

        'template_tenant_connection' => null,

        'prefix' => env('DB_PREFIX', 'tenant_'),

        'suffix' => '',

        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql'  => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql'  => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache tenancy config
    |--------------------------------------------------------------------------
    | Cache keys are automatically prefixed with the tenant ID when
    | CacheTenancyBootstrapper is active, preventing cross-tenant pollution.
    */
    'cache' => [
        'tag_base' => 'tenant', // Cache tags: tenant_{id}
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem tenancy config
    |--------------------------------------------------------------------------
    | Maps config keys to their tenant-specific overrides. Storage paths are
    | prefixed with /tenant_{id}/ automatically.
    */
    'filesystem' => [
        'suffix_base' => 'tenant_',

        'disks' => [
            'local',
            'public',
            // 's3', // Uncomment when using S3
        ],

        'root_override' => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        'suffix_storage_path' => true,

        'asset_helper_tenancy' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'tenant_route_namespace' => 'App\\Http\\Controllers',
        'middleware' => [
            'universal' => [],
            'central'   => ['web'],
            'tenant'    => [
                'web',
                // Custom middleware handles both *.novaxpos.com subdomains AND
                // white-label custom domains stored in tenant data JSON.
                App\Http\Middleware\InitializeTenancyByDomain::class,
                // PreventAccessFromCentralDomains is intentionally removed —
                // it blocks all custom domains that aren't *.novaxpos.com.
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,  // enable for support access
        // Stancl\Tenancy\Features\TelescopeTags::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration parameters
    |--------------------------------------------------------------------------
    | Migrations in database/migrations/tenant run inside each tenant's DB.
    | Migrations in database/migrations run in the central (landlord) DB.
    */
    'migration_parameters' => [
        '--force'    => true,
        '--database' => 'tenant',           // must be explicit — without this, MigrateCommand
                                            // can fall back to the mysql (landlord) connection
        '--path'     => [database_path('migrations')],
        '--realpath' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeder parameters
    |--------------------------------------------------------------------------
    */
    'seeder_parameters' => [
        '--force' => true,
    ],

];
