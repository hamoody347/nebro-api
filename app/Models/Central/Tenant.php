<?php

declare(strict_types=1);

namespace App\Models\Central;

use App\Enums\Submodule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

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

    public function submodules(): HasMany
    {
        return $this->hasMany(TenantSubmodule::class);
    }

    /**
     * Returns the list of enabled submodule values for this tenant.
     * Result is request-scoped (lazy-loaded once, then cached on the model instance).
     */
    public function enabledSubmodules(): array
    {
        return $this->_enabledSubmodules
            ??= $this->submodules()->pluck('submodule')->map(fn ($v) => $v instanceof Submodule ? $v->value : $v)->all();
    }

    public function hasSubmodule(Submodule|string $submodule): bool
    {
        $value = $submodule instanceof Submodule ? $submodule->value : $submodule;

        return in_array($value, $this->enabledSubmodules(), true);
    }

    /** @internal Request-scoped submodule cache — reset by assigning null. */
    private ?array $_enabledSubmodules = null;
}
