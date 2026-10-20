<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'shipping_address' => fake()->address(),
            'status' => OrderStatus::Pending,
            'total' => 0,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => OrderStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => OrderStatus::Cancelled]);
    }

    /**
     * Attach items and keep the stored total consistent with them.
     */
    public function withItems(int $count = 2, float $price = 50.0, int $quantity = 2): static
    {
        return $this->afterCreating(function (Order $order) use ($count, $price, $quantity) {
            for ($i = 1; $i <= $count; $i++) {
                $order->items()->create([
                    'product_name' => "Product {$i}",
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }

            $order->update(['total' => round($count * $quantity * $price, 2)]);
        });
    }
}
