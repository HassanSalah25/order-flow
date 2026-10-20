<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fakes\FakeWalletGateway;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->actingAsJwt();
    }

    private function confirmedOrder(): Order
    {
        return Order::factory()->for($this->user)->confirmed()->withItems(2, 50, 2)->create();
    }

    private function card(array $overrides = []): array
    {
        return [
            'method' => 'credit_card',
            'payment_details' => [
                'card_number' => '4242424242424242',
                'card_holder' => 'Jane Doe',
                'expiry_month' => 12,
                'expiry_year' => now()->addYear()->year,
                'cvv' => '123',
                ...$overrides,
            ],
        ];
    }

    public function test_confirmed_order_can_be_paid_by_credit_card(): void
    {
        $order = $this->confirmedOrder();

        $this->postJson("/api/orders/{$order->id}/payments", $this->card())
            ->assertCreated()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.method', 'credit_card')
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.amount', 200)
            ->assertJsonPath('data.meta.card_last4', '4242');

        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'successful']);
    }

    public function test_confirmed_order_can_be_paid_by_paypal(): void
    {
        $order = $this->confirmedOrder();

        $this->postJson("/api/orders/{$order->id}/payments", [
            'method' => 'paypal',
            'payment_details' => ['paypal_email' => 'buyer@example.com'],
        ])->assertCreated()->assertJsonPath('data.status', 'successful');
    }

    public function test_declined_payment_is_recorded_as_failed(): void
    {
        $order = $this->confirmedOrder();

        $this->postJson("/api/orders/{$order->id}/payments", $this->card(['card_number' => CreditCardGateway::DECLINED_CARD]))
            ->assertCreated()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.failure_reason', 'Card declined by issuer.');
    }

    public function test_failed_payment_can_be_retried(): void
    {
        $order = $this->confirmedOrder();
        Payment::factory()->for($order)->failed()->create();

        $this->postJson("/api/orders/{$order->id}/payments", $this->card())
            ->assertCreated()
            ->assertJsonPath('data.status', 'successful');
    }

    public static function nonConfirmedStatuses(): array
    {
        return [['pending'], ['cancelled']];
    }

    #[DataProvider('nonConfirmedStatuses')]
    public function test_payment_is_rejected_unless_order_is_confirmed(string $status): void
    {
        $order = Order::factory()->for($this->user)->withItems()->create(['status' => $status]);

        $this->postJson("/api/orders/{$order->id}/payments", $this->card())
            ->assertUnprocessable()
            ->assertJson(['message' => 'Payments can only be processed for orders in the confirmed status.']);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_already_paid_order_cannot_be_paid_again(): void
    {
        $order = $this->confirmedOrder();
        Payment::factory()->for($order)->create();

        $this->postJson("/api/orders/{$order->id}/payments", $this->card())->assertConflict();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_unsupported_method_is_rejected(): void
    {
        $order = $this->confirmedOrder();

        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'bitcoin', 'payment_details' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('method');
    }

    public function test_gateway_specific_details_are_validated(): void
    {
        $order = $this->confirmedOrder();

        $this->postJson("/api/orders/{$order->id}/payments", [
            'method' => 'credit_card',
            'payment_details' => ['card_number' => 'abc'],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'payment_details.card_number',
            'payment_details.card_holder',
            'payment_details.cvv',
        ]);

        $this->postJson("/api/orders/{$order->id}/payments", [
            'method' => 'paypal',
            'payment_details' => ['paypal_email' => 'nope'],
        ])->assertUnprocessable()->assertJsonValidationErrors('payment_details.paypal_email');
    }

    public function test_user_cannot_pay_another_users_order(): void
    {
        $order = Order::factory()->confirmed()->withItems()->create();

        $this->postJson("/api/orders/{$order->id}/payments", $this->card())->assertForbidden();
    }

    public function test_a_new_gateway_registered_in_config_works_end_to_end(): void
    {
        config(['payments.gateways.fake_wallet' => ['driver' => FakeWalletGateway::class, 'api_key' => 'k']]);
        $this->app->forgetInstance(PaymentGatewayManager::class);
        $order = $this->confirmedOrder();

        $this->postJson("/api/orders/{$order->id}/payments", ['method' => 'fake_wallet', 'payment_details' => ['x' => 1]])
            ->assertCreated()
            ->assertJsonPath('data.method', 'fake_wallet')
            ->assertJsonPath('data.status', 'successful');
    }

    public function test_user_can_list_payments_for_an_order(): void
    {
        $order = $this->confirmedOrder();
        Payment::factory()->count(2)->for($order)->failed()->create();
        Payment::factory()->create(); // someone else's

        $this->getJson("/api/orders/{$order->id}/payments")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_can_list_all_their_payments_with_filters(): void
    {
        $order = $this->confirmedOrder();
        Payment::factory()->count(3)->for($order)->failed()->create();
        Payment::factory()->for($order)->create();
        Payment::factory()->count(2)->create(); // other users

        $this->getJson('/api/payments?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 4);

        $this->getJson('/api/payments?status=successful')->assertJsonPath('meta.total', 1);
    }

    public function test_user_can_view_a_single_payment_but_not_others(): void
    {
        $mine = Payment::factory()->for($this->confirmedOrder())->create();
        $theirs = Payment::factory()->create();

        $this->getJson("/api/payments/{$mine->id}")->assertOk()->assertJsonPath('data.id', $mine->id);
        $this->getJson("/api/payments/{$theirs->id}")->assertForbidden();
    }

    public function test_supported_methods_are_listed(): void
    {
        $this->getJson('/api/payment-methods')
            ->assertOk()
            ->assertJson(['data' => ['credit_card', 'paypal']]);
    }
}
