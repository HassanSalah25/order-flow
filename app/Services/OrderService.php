<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private const ORDER_FIELDS = ['customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'status'];

    /**
     * @param  array<string, mixed>  $data  Validated payload including "items".
     */
    public function create(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $order = $user->orders()->create([
                ...Arr::only($data, self::ORDER_FIELDS),
                'status' => $data['status'] ?? OrderStatus::Pending->value,
                'total' => 0,
            ]);

            $this->syncItems($order, $data['items']);

            return $order->load('items');
        });
    }

    /**
     * @param  array<string, mixed>  $data  Validated payload; every key is optional.
     */
    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            if (array_key_exists('items', $data)) {
                if ($order->hasPayments()) {
                    throw BusinessRuleException::itemsLocked();
                }

                $order->items()->delete();
                $this->syncItems($order, $data['items']);
            }

            $order->update(Arr::only($data, self::ORDER_FIELDS));

            return $order->load('items');
        });
    }

    public function delete(Order $order): void
    {
        if ($order->hasPayments()) {
            throw BusinessRuleException::orderHasPayments();
        }

        $order->delete();
    }

    /**
     * @param  list<array{product_name: string, quantity: int, price: float|string}>  $items
     */
    public function calculateTotal(array $items): float
    {
        $total = array_reduce(
            $items,
            fn (float $sum, array $item) => $sum + $item['quantity'] * (float) $item['price'],
            0.0,
        );

        return round($total, 2);
    }

    private function syncItems(Order $order, array $items): void
    {
        $order->items()->createMany(
            array_map(fn (array $item) => Arr::only($item, ['product_name', 'quantity', 'price']), $items),
        );

        // Total is always computed server-side; clients never send it.
        $order->update(['total' => $this->calculateTotal($items)]);
    }
}
