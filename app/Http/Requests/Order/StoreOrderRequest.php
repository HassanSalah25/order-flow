<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            ...self::itemRules('required'),
        ];
    }

    /**
     * Shared item rules so store and update stay in sync.
     *
     * @return array<string, mixed>
     */
    public static function itemRules(string $presence): array
    {
        return [
            'items' => [$presence, 'array', 'min:1', 'max:100'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.price' => ['required', 'numeric', 'min:0.01', 'max:1000000', 'decimal:0,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'An order must contain at least one item.',
            'items.min' => 'An order must contain at least one item.',
            'items.*.product_name.required' => 'Each item needs a product name.',
            'items.*.quantity.min' => 'Each item quantity must be at least 1.',
            'items.*.price.min' => 'Each item price must be greater than zero.',
        ];
    }
}
