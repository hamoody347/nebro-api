<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Module;
use App\Enums\Submodule;

class TenantModuleService
{
    public function enabled(Submodule|string $submodule): bool
    {
        if (! tenancy()->initialized()) {
            return false;
        }

        return tenancy()->tenant->hasSubmodule($submodule);
    }

    public function enabledSubmodules(): array
    {
        if (! tenancy()->initialized()) {
            return [];
        }

        return tenancy()->tenant->enabledSubmodules();
    }

    /**
     * Returns enabled Submodule cases grouped by their parent Module value.
     *
     * Example: ['invoicing' => [Submodule::InvoicingQuotes], 'reports' => [...]]
     */
    public function enabledByModule(): array
    {
        $enabled = $this->enabledSubmodules();
        $grouped = [];

        foreach (Submodule::cases() as $submodule) {
            if (in_array($submodule->value, $enabled, true)) {
                $grouped[$submodule->module()->value][] = $submodule;
            }
        }

        return $grouped;
    }
}
