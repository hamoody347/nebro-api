<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantScoutImport extends Command
{
    protected $signature   = 'tenants:scout-import {model : Fully-qualified model class to import}';
    protected $description = 'Import a searchable model into Meilisearch for every tenant.';

    public function handle(): int
    {
        $model   = $this->argument('model');
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $this->info("Importing [{$model}] for tenant [{$tenant->id}]...");

            $tenant->run(function () use ($model) {
                Artisan::call('scout:import', ['model' => $model]);
                $this->line(Artisan::output());
            });
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
