<?php

declare(strict_types=1);

namespace App\Enums;

enum Module: string
{
    case Invoicing = 'invoicing';
    case Reports   = 'reports';

    public function label(): string
    {
        return match($this) {
            self::Invoicing => 'Invoicing',
            self::Reports   => 'Reports',
        };
    }

    /** Returns all Submodule cases that belong to this module. */
    public function submodules(): array
    {
        return array_values(array_filter(
            Submodule::cases(),
            fn (Submodule $s) => $s->module() === $this,
        ));
    }
}
