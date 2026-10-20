<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\Exceptions\UnsupportedPaymentMethodException;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the gateway strategy for a payment method from config/payments.php.
 * This class never needs to change when a new gateway is added.
 */
class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $resolved = [];

    /**
     * @param  array<string, array<string, mixed>>  $gateways
     */
    public function __construct(
        private readonly Container $container,
        private readonly array $gateways,
    ) {
    }

    /**
     * @return list<string>
     */
    public function supportedMethods(): array
    {
        $enabled = array_filter($this->gateways, fn (array $config) => $config['enabled'] ?? true);

        return array_keys($enabled);
    }

    public function supports(string $method): bool
    {
        return in_array($method, $this->supportedMethods(), true);
    }

    public function gateway(string $method): PaymentGatewayInterface
    {
        if (! $this->supports($method)) {
            throw UnsupportedPaymentMethodException::forMethod($method);
        }

        return $this->resolved[$method] ??= $this->build($method);
    }

    private function build(string $method): PaymentGatewayInterface
    {
        $config = $this->gateways[$method];
        $gateway = $this->container->make($config['driver'], ['config' => $config]);

        if (! $gateway instanceof PaymentGatewayInterface) {
            throw UnsupportedPaymentMethodException::invalidDriver($method);
        }

        return $gateway;
    }
}
