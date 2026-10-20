<?php

namespace Tests\Unit\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Gateways\PaypalGateway;
use Tests\TestCase;

class PaypalGatewayTest extends TestCase
{
    public function test_valid_account_is_charged_successfully(): void
    {
        $gateway = new PaypalGateway(['mode' => 'sandbox']);

        $result = $gateway->charge(new Order(), ['paypal_email' => 'buyer@example.com']);

        $this->assertTrue($result->isSuccessful());
        $this->assertStringStartsWith('pp_', $result->transactionReference);
        $this->assertSame(['payer_email' => 'buyer@example.com', 'mode' => 'sandbox'], $result->meta);
    }

    public function test_declined_domain_fails(): void
    {
        $gateway = new PaypalGateway();

        $result = $gateway->charge(new Order(), ['paypal_email' => 'buyer@DECLINE.test']);

        $this->assertSame(PaymentStatus::Failed, $result->status);
        $this->assertSame('PayPal account rejected the payment.', $result->failureReason);
    }

    public function test_it_reads_mode_from_its_config(): void
    {
        $gateway = new PaypalGateway(['mode' => 'live']);

        $result = $gateway->charge(new Order(), ['paypal_email' => 'buyer@example.com']);

        $this->assertSame('live', $result->meta['mode']);
    }
}
