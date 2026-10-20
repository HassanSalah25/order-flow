<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->confirmed(),
            'method' => 'credit_card',
            'status' => PaymentStatus::Successful,
            'amount' => 100,
            'transaction_reference' => 'cc_'.Str::upper(Str::random(16)),
            'meta' => ['card_last4' => '4242'],
        ];
    }

    public function failed(): static
    {
        return $this->state(['status' => PaymentStatus::Failed, 'failure_reason' => 'Card declined by issuer.']);
    }
}
