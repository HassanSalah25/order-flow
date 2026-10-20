<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' => 'Status must be one of: '.implode(', ', OrderStatus::values()).'.',
        ];
    }
}
