<?php

namespace App\Http\Requests\Payment;

use App\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        $gateways = $this->container->make(PaymentGatewayManager::class);
        $method = $this->input('method');

        $rules = [
            'method' => ['required', 'string', Rule::in($gateways->supportedMethods())],
            'payment_details' => ['required', 'array'],
        ];

        // Each gateway contributes its own rules, so new gateways need no change here.
        if (is_string($method) && $gateways->supports($method)) {
            foreach ($gateways->gateway($method)->rules() as $field => $fieldRules) {
                $rules["payment_details.{$field}"] = $fieldRules;
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        $supported = implode(', ', $this->container->make(PaymentGatewayManager::class)->supportedMethods());

        return [
            'method.in' => "The selected payment method is not supported. Supported methods: {$supported}.",
        ];
    }
}
