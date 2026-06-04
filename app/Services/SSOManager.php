<?php

declare(strict_types=1);

namespace App\Services;

use App\Adapters\SSO\GoogleAdapter;
use App\Contracts\SSO\SSOAdapterInterface;
use InvalidArgumentException;

class SSOManager
{
    /** @var array<string, callable> */
    private array $factories;

    public function __construct(array $factories = [])
    {
        $this->factories = $factories ?: [
            'google' => fn () => new GoogleAdapter(config('sso.google', [])),
        ];
    }

    public function make(string $provider): SSOAdapterInterface
    {
        if (! isset($this->factories[$provider])) {
            throw new InvalidArgumentException("Unsupported SSO provider: [{$provider}].");
        }

        return ($this->factories[$provider])();
    }

    public function supports(string $provider): bool
    {
        return isset($this->factories[$provider]);
    }
}
