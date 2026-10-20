<?php

namespace Tests\Fakes;

use App\Models\Order;
use App\Payments\Gateways\AbstractPaymentGateway;
use App\Payments\PaymentResult;

/**
 * A throwaway gateway proving that a new payment method needs only a class + config entry.
 */
class FakeWalletGateway extends AbstractPaymentGateway
{
    public function rules(): array
    {
        return ['x' => ['required']];
    }

    public function charge(Order $order, array $details): PaymentResult
    {
        return PaymentResult::successful('fw_'.$order->id, ['api_key' => $this->config('api_key')]);
    }
}
