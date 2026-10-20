<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'required', 'email', 'max:255'],
            'customer_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'shipping_address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'required', Rule::enum(OrderStatus::class)],
            ...StoreOrderRequest::itemRules('sometimes'),
        ];
    }

    public function messages(): array
    {
        return (new StoreOrderRequest())->messages();
    }
}
