<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPaymentsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(PaymentStatus::class)],
            'method' => ['sometimes', 'string', 'max:50'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
