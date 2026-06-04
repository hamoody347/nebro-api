<?php

declare(strict_types=1);

namespace App\Models\Central;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    protected $connection = 'central';
    // $incrementing and $keyType are managed by stancl's GeneratesIds concern.

    /**
     * Columns stored as real DB columns (not JSON-encoded in `data`).
     */
    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'plan', 'created_at', 'updated_at'];
    }
}
