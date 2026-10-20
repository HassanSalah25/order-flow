<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\PaymentResult;
use Illuminate\Support\Str;

/**
 * Simulated PayPal processor. In production this would use
 * $this->config('client_id') / $this->config('secret') against $this->config('mode').
 */
class PaypalGateway extends AbstractPaymentGateway
{
    /** Payer emails on this domain are always rejected (sandbox convention). */
    public const DECLINED_DOMAIN = '@decline.test';

    public function rules(): array
    {
        return [
            'paypal_email' => ['required', 'email', 'max:255'],
        ];
    }

    public function charge(Order $order, array $details): PaymentResult
    {
        $email = (string) $details['paypal_email'];
        $meta = ['payer_email' => $email, 'mode' => $this->config('mode', 'sandbox')];

        if (Str::endsWith(Str::lower($email), self::DECLINED_DOMAIN)) {
            return PaymentResult::failed('PayPal account rejected the payment.', $this->newReference('pp'), $meta);
        }

        return PaymentResult::successful($this->newReference('pp'), $meta);
    }
}
