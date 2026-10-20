<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\PaymentResult;

/**
 * Every payment gateway is a strategy implementing this contract.
 * Adding a gateway = one class implementing this interface + one entry in config/payments.php.
 */
interface PaymentGatewayInterface
{
    /**
     * Validation rules for the gateway-specific "payment_details" payload.
     * Keys are relative to "payment_details" (e.g. "card_number").
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * Charge the given order and report the outcome.
     *
     * @param  array<string, mixed>  $details  Validated "payment_details" payload.
     */
    public function charge(Order $order, array $details): PaymentResult;
}
