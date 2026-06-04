<?php

declare(strict_types=1);

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class SocialIdentity extends Model
{
    protected $connection = 'central';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'access_token'     => 'encrypted',
            'refresh_token'    => 'encrypted',
            'raw_data'         => 'array',
            'token_expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
