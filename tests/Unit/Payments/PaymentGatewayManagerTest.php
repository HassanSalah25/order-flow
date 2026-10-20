<?php

namespace Tests\Unit\Payments;

use App\Models\Order;
use App\Payments\Exceptions\UnsupportedPaymentMethodException;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PaypalGateway;
use App\Payments\PaymentGatewayManager;
use stdClass;
use Tests\Fakes\FakeWalletGateway;
use Tests\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    private function manager(array $gateways): PaymentGatewayManager
    {
        return new PaymentGatewayManager($this->app, $gateways);
    }

    public function test_it_resolves_the_configured_strategy_for_each_method(): void
    {
        $manager = $this->manager([
            'credit_card' => ['driver' => CreditCardGateway::class],
            'paypal' => ['driver' => PaypalGateway::class],
        ]);

        $this->assertInstanceOf(CreditCardGateway::class, $manager->gateway('credit_card'));
        $this->assertInstanceOf(PaypalGateway::class, $manager->gateway('paypal'));
    }

    public function test_it_caches_resolved_gateways(): void
    {
        $manager = $this->manager(['paypal' => ['driver' => PaypalGateway::class]]);

        $this->assertSame($manager->gateway('paypal'), $manager->gateway('paypal'));
    }

    public function test_disabled_gateways_are_not_supported(): void
    {
        $manager = $this->manager([
            'credit_card' => ['driver' => CreditCardGateway::class, 'enabled' => true],
            'paypal' => ['driver' => PaypalGateway::class, 'enabled' => false],
        ]);

        $this->assertSame(['credit_card'], $manager->supportedMethods());
        $this->expectException(UnsupportedPaymentMethodException::class);
        $manager->gateway('paypal');
    }

    public function test_unknown_method_throws(): void
    {
        $this->expectException(UnsupportedPaymentMethodException::class);
        $this->expectExceptionMessage('Payment method [bitcoin] is not supported.');

        $this->manager([])->gateway('bitcoin');
    }

    public function test_driver_must_implement_the_gateway_contract(): void
    {
        $this->expectException(UnsupportedPaymentMethodException::class);

        $this->manager(['broken' => ['driver' => stdClass::class]])->gateway('broken');
    }

    public function test_a_new_gateway_needs_only_a_class_and_a_config_entry(): void
    {
        $manager = $this->manager([
            'fake_wallet' => ['driver' => FakeWalletGateway::class, 'api_key' => 'secret-key'],
        ]);

        $result = $manager->gateway('fake_wallet')->charge(new Order(), ['x' => 1]);

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('secret-key', $result->meta['api_key']);
    }
}
