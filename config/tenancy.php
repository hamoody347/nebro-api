<?php

declare(strict_types=1);

use App\Models\Central\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

return [
    'tenant_model' => Tenant::class,

    // When creating a tenant with an explicit 'id', the generator is skipped.
    // When creating without an explicit 'id', a UUID is auto-generated.
    // Human-readable slugs (e.g. "acme-corp") are set by passing 'id' explicitly.
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,

    /**
     * Central domains — used by PreventAccessFromCentralDomains middleware.
     * Not relevant for header-based tenancy, but kept for future flexibility.
     */
    'central_domains' => [
        '127.0.0.1',
        'localhost',
    ],

    /**
     * Bootstrappers run when tenancy is initialized.
     * RedisTenancyBootstrapper requires phpredis; omitted here since we use predis.
     * CacheTenancyBootstrapper uses cache tags (compatible with Redis).
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class,
        // FilesystemTenancyBootstrapper excluded — this is a pure API; no tenant-specific local disk needed.
    ],

    /**
     * Database tenancy config. Used by DatabaseTenancyBootstrapper.
     */
    'database' => [
        // The central DB connection. All central models use this.
        // Must match the named connection in config/database.php, NOT the DB_CONNECTION env var.
        'central_connection' => 'central',

        // Template connection cloned per tenant. Must not be named 'tenant'.
        'template_tenant_connection' => null,

        // Tenant DB name = prefix + tenant_id + suffix → e.g. "tenant_acme-corp"
        'prefix' => 'tenant_',
        'suffix' => '',

        'managers' => [
            'sqlite'  => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql'   => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'mariadb' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql'   => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    /**
     * Cache tenancy config. CacheTenancyBootstrapper tags every cache key with the tenant ID.
     * Works with Redis cache (requires taggable store).
     */
    'cache' => [
        'tag_base' => 'tenant',
    ],

    /**
     * Filesystem tenancy config.
     * Disabled above (FilesystemTenancyBootstrapper not in bootstrappers).
     */
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => ['local', 'public'],
        'root_override' => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => false,
    ],

    /**
     * Redis tenancy config.
     * RedisTenancyBootstrapper requires phpredis; not enabled until phpredis is available.
     */
    'redis' => [
        'prefix_base' => 'tenant',
        // Only prefix these connections. session and queue are intentionally excluded:
        // sessions are scoped by cookie, queue jobs carry tenant ID in the payload.
        'prefixed_connections' => ['default', 'cache'],
    ],

    'features' => [],

    'routes' => false, // Disable stancl asset routes (pure API, no tenant assets)

    /**
     * Parameters passed to tenants:migrate command.
     * All tenant-scoped migrations live in database/migrations/tenant/.
     */
    'migration_parameters' => [
        '--force'     => true,
        '--path'      => [database_path('migrations/tenant')],
        '--realpath'  => true,
    ],

    /**
     * Parameters passed to tenants:seed command.
     */
    'seeder_parameters' => [
        '--class' => 'TenantDatabaseSeeder',
    ],
];
