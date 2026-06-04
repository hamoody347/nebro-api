<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class LoggingService
{
    private function context(): array
    {
        return [
            'tenant_id' => tenancy()->initialized() ? tenancy()->tenant->id : 'central',
        ];
    }

    public function tenant(): LoggerInterface
    {
        return Log::channel('tenant')->withContext($this->context());
    }

    public function auth(): LoggerInterface
    {
        return Log::channel('auth')->withContext($this->context());
    }

    public function payment(): LoggerInterface
    {
        return Log::channel('payment')->withContext($this->context());
    }

    public function system(): LoggerInterface
    {
        return Log::channel('system')->withContext($this->context());
    }
}
