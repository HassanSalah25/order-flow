<?php

namespace App\Payments\Gateways;

use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Shared plumbing for gateways: access to their config block (keys/secrets from .env)
 * and transaction reference generation.
 */
abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    /**
     * @param  array<string, mixed>  $config  The gateway's block from config/payments.php.
     */
    public function __construct(protected readonly array $config = [])
    {
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    protected function newReference(string $prefix): string
    {
        return $prefix.'_'.Str::upper(Str::random(16));
    }
}
