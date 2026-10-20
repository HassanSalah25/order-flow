<?php

namespace App\Payments;

use App\Enums\PaymentStatus;

/**
 * Immutable outcome returned by a gateway, decoupling gateways from the Payment model.
 */
final readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    private function __construct(
        public PaymentStatus $status,
        public ?string $transactionReference = null,
        public ?string $failureReason = null,
        public array $meta = [],
    ) {
    }

    public static function successful(string $transactionReference, array $meta = []): self
    {
        return new self(PaymentStatus::Successful, $transactionReference, null, $meta);
    }

    public static function failed(string $reason, ?string $transactionReference = null, array $meta = []): self
    {
        return new self(PaymentStatus::Failed, $transactionReference, $reason, $meta);
    }

    public static function pending(string $transactionReference, array $meta = []): self
    {
        return new self(PaymentStatus::Pending, $transactionReference, null, $meta);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Successful;
    }
}
