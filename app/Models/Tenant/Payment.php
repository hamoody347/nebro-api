<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'gateway',
        'amount',
        'currency',
        'status',
        'gateway_payment_id',
        'gateway_payment_intent_id',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'paid_at'  => 'datetime',
        ];
    }

    public function payer(): MorphTo
    {
        return $this->morphTo();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
