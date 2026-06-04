<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use LogsActivity;
    use Notifiable;

    // No $connection override — stancl/tenancy switches this at runtime per request.

    protected $fillable = [
        'central_user_id',
        'name',
        'email',
        'password',
        'timezone',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at'    => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName(tenancy()->initialized() ? (string) tenancy()->tenant->id : 'system')
            ->dontSubmitEmptyLogs();
    }
}
