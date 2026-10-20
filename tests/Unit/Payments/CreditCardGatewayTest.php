<?php

namespace Tests\Unit\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Gateways\CreditCardGateway;
use Tests\TestCase;

class CreditCardGatewayTest extends TestCase
{
    private CreditCardGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new CreditCardGateway(['api_key' => 'test']);
    }

    private function details(array $overrides = []): array
    {
        return [
            'card_number' => '4242424242424242',
            'card_holder' => 'Jane Doe',
            'expiry_month' => 12,
            'expiry_year' => now()->addYear()->year,
            'cvv' => '123',
            ...$overrides,
        ];
    }

    public function test_valid_card_is_charged_successfully(): void
    {
        $result = $this->gateway->charge(new Order(), $this->details());

        $this->assertTrue($result->isSuccessful());
        $this->assertStringStartsWith('cc_', $result->transactionReference);
        $this->assertNull($result->failureReason);
    }

    public function test_declined_test_card_fails(): void
    {
        $result = $this->gateway->charge(new Order(), $this->details(['card_number' => CreditCardGateway::DECLINED_CARD]));

        $this->assertSame(PaymentStatus::Failed, $result->status);
        $this->assertSame('Card declined by issuer.', $result->failureReason);
    }

    public function test_expired_card_fails(): void
    {
        $lastMonth = now()->subMonth();
        $result = $this->gateway->charge(new Order(), $this->details([
            'expiry_month' => $lastMonth->month,
            'expiry_year' => $lastMonth->year,
        ]));

        $this->assertSame(PaymentStatus::Failed, $result->status);
        $this->assertSame('Card has expired.', $result->failureReason);
    }

    public function test_only_last_four_digits_are_kept_in_meta(): void
    {
        $result = $this->gateway->charge(new Order(), $this->details());

        $this->assertSame(['card_last4' => '4242'], $result->meta);
    }

    public function test_it_declares_card_validation_rules(): void
    {
        $this->assertEqualsCanonicalizing(
            ['card_number', 'card_holder', 'expiry_month', 'expiry_year', 'cvv'],
            array_keys($this->gateway->rules()),
        );
    }
}
