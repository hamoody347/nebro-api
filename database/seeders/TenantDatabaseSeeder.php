<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Submodule;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Roles ────────────────────────────────────────────────────────────────
        foreach (['super-admin', 'admin', 'member'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // ── Permissions (all submodules, all actions) ────────────────────────────
        // Permissions are seeded for every submodule regardless of which submodules
        // the tenant has enabled. The RequiresTenantModule middleware (Layer 1)
        // controls access — permission records are decoupled from feature availability.
        foreach (Submodule::cases() as $submodule) {
            foreach ($submodule->qualifiedPermissions() as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            }
        }

        // super-admin receives all permissions across all submodules
        Role::findByName('super-admin', 'web')->syncPermissions(Permission::all());
    }
}
