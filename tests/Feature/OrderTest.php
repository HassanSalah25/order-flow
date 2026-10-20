<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->actingAsJwt();
    }

    private function payload(array $overrides = []): array
    {
        return [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '+966500000000',
            'shipping_address' => 'Riyadh, KSA',
            'items' => [
                ['product_name' => 'Keyboard', 'quantity' => 2, 'price' => 150.25],
                ['product_name' => 'Mouse', 'quantity' => 1, 'price' => 49.5],
            ],
            ...$overrides,
        ];
    }

    public function test_user_can_create_an_order_and_total_is_calculated_server_side(): void
    {
        $response = $this->postJson('/api/orders', $this->payload(['total' => 1]));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total', 350)
            ->assertJsonPath('data.items.0.subtotal', 300.5)
            ->assertJsonCount(2, 'data.items');
        $this->assertDatabaseHas('orders', ['user_id' => $this->user->id, 'total' => 350]);
    }

    public function test_order_creation_is_validated(): void
    {
        $this->postJson('/api/orders', [
            'customer_email' => 'not-an-email',
            'items' => [['product_name' => '', 'quantity' => 0, 'price' => -5]],
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'customer_name',
            'customer_email',
            'items.0.product_name',
            'items.0.quantity',
            'items.0.price',
        ]);
    }

    public function test_order_requires_at_least_one_item(): void
    {
        $this->postJson('/api/orders', $this->payload(['items' => []]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.items.0', 'An order must contain at least one item.');
    }

    public function test_user_can_list_orders_with_pagination(): void
    {
        Order::factory()->count(20)->for($this->user)->withItems()->create();
        Order::factory()->count(3)->create(); // another user's orders

        $this->getJson('/api/orders?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        Order::factory()->count(2)->for($this->user)->confirmed()->create();
        Order::factory()->count(3)->for($this->user)->create();

        $this->getJson('/api/orders?status=confirmed')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'confirmed');
    }

    public function test_invalid_status_filter_is_rejected(): void
    {
        $this->getJson('/api/orders?status=shipped')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_user_can_view_an_order(): void
    {
        $order = Order::factory()->for($this->user)->withItems()->create();

        $this->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.payments_count', 0);
    }

    public function test_user_cannot_access_another_users_order(): void
    {
        $order = Order::factory()->create();

        $this->getJson("/api/orders/{$order->id}")->assertForbidden();
        $this->putJson("/api/orders/{$order->id}", ['status' => 'confirmed'])->assertForbidden();
        $this->deleteJson("/api/orders/{$order->id}")->assertForbidden();
    }

    public function test_missing_order_returns_404(): void
    {
        $this->getJson('/api/orders/999')
            ->assertNotFound()
            ->assertJson(['message' => 'Order not found.']);
    }

    public function test_user_can_update_order_details_and_status(): void
    {
        $order = Order::factory()->for($this->user)->withItems()->create();

        $this->putJson("/api/orders/{$order->id}", ['customer_name' => 'John', 'status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.customer.name', 'John')
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_updating_items_recalculates_the_total(): void
    {
        $order = Order::factory()->for($this->user)->withItems()->create();

        $this->patchJson("/api/orders/{$order->id}", [
            'items' => [['product_name' => 'Monitor', 'quantity' => 2, 'price' => 500]],
        ])->assertOk()->assertJsonPath('data.total', 1000)->assertJsonCount(1, 'data.items');

        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_items_cannot_change_after_payments_exist(): void
    {
        $order = Order::factory()->for($this->user)->confirmed()->withItems()->create();
        Payment::factory()->for($order)->failed()->create();

        $this->patchJson("/api/orders/{$order->id}", [
            'items' => [['product_name' => 'Monitor', 'quantity' => 1, 'price' => 1]],
        ])->assertConflict();
    }

    public function test_user_can_delete_an_order_without_payments(): void
    {
        $order = Order::factory()->for($this->user)->withItems()->create();

        $this->deleteJson("/api/orders/{$order->id}")->assertNoContent();
        $this->assertModelMissing($order);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_order_with_payments_cannot_be_deleted(): void
    {
        $order = Order::factory()->for($this->user)->confirmed()->withItems()->create();
        Payment::factory()->for($order)->create();

        $this->deleteJson("/api/orders/{$order->id}")
            ->assertConflict()
            ->assertJson(['message' => 'Orders with associated payments cannot be deleted.']);
        $this->assertModelExists($order);
    }
}
