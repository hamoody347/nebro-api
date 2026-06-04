<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'gateway',
        'event_type',
        'payload',
        'signature',
        'processed_at',
        'processing_error',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
