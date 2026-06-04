<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default roles for every new tenant.
        // Permission checks require setPermissionsTeamId() before querying; the
        // TenancyServiceProvider's SeedDatabase job runs with tenant context active.
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = ['super-admin', 'admin', 'member'];

        foreach ($roles as $name) {
            Role::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }
    }
}
