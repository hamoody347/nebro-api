<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Payment\PaymentGateway;
use App\Gateways\StripeGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    private array $resolved = [];

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= config('payment.default');

        return $this->resolved[$name] ??= $this->resolve($name);
    }

    private function resolve(string $name): PaymentGateway
    {
        $config = config("payment.gateways.{$name}");

        if ($config === null) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not configured.");
        }

        return match ($name) {
            'stripe' => new StripeGateway($config),
            default  => throw new InvalidArgumentException("Payment gateway [{$name}] has no registered driver."),
        };
    }
}
