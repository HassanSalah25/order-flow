<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'method' => $this->method,
            'status' => $this->status->value,
            'amount' => (float) $this->amount,
            'transaction_reference' => $this->transaction_reference,
            'failure_reason' => $this->failure_reason,
            'meta' => $this->meta ?? (object) [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
