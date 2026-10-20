<?php

namespace App\Payments\Exceptions;

use InvalidArgumentException;

class UnsupportedPaymentMethodException extends InvalidArgumentException
{
    public static function forMethod(string $method): self
    {
        return new self("Payment method [{$method}] is not supported.");
    }

    public static function invalidDriver(string $method): self
    {
        return new self("Gateway driver for [{$method}] must implement PaymentGatewayInterface.");
    }
}
