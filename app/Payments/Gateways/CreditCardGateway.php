<?php

namespace App\Payments\Gateways;

use App\Models\Order;
use App\Payments\PaymentResult;

/**
 * Simulated card processor. In production this would call the acquirer's API
 * using $this->config('api_key') / $this->config('secret').
 */
class CreditCardGateway extends AbstractPaymentGateway
{
    /** Test card that is always declined (mirrors common sandbox conventions). */
    public const DECLINED_CARD = '4000000000000002';

    public function rules(): array
    {
        return [
            'card_number' => ['required', 'digits_between:13,19'],
            'card_holder' => ['required', 'string', 'max:255'],
            'expiry_month' => ['required', 'integer', 'between:1,12'],
            'expiry_year' => ['required', 'integer', 'min:'.now()->year],
            'cvv' => ['required', 'digits_between:3,4'],
        ];
    }

    public function charge(Order $order, array $details): PaymentResult
    {
        $cardNumber = (string) $details['card_number'];
        // Only the last 4 digits are ever persisted.
        $meta = ['card_last4' => substr($cardNumber, -4)];

        if ($cardNumber === self::DECLINED_CARD) {
            return PaymentResult::failed('Card declined by issuer.', $this->newReference('cc'), $meta);
        }

        if ($this->isExpired((int) $details['expiry_month'], (int) $details['expiry_year'])) {
            return PaymentResult::failed('Card has expired.', $this->newReference('cc'), $meta);
        }

        return PaymentResult::successful($this->newReference('cc'), $meta);
    }

    private function isExpired(int $month, int $year): bool
    {
        return now()->startOfMonth()->greaterThan(now()->setDate($year, $month, 1)->startOfMonth());
    }
}
