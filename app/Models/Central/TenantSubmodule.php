<?php

declare(strict_types=1);

namespace App\Models\Central;

use App\Enums\Submodule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubmodule extends Model
{
    protected $connection = 'central';

    protected $fillable = ['tenant_id', 'submodule'];

    protected function casts(): array
    {
        return [
            'submodule' => Submodule::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
