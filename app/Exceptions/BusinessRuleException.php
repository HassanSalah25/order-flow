<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thrown when a request is valid but conflicts with the current state of a resource.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = Response::HTTP_CONFLICT)
    {
        parent::__construct($message);
    }

    public static function orderNotConfirmed(): self
    {
        return new self('Payments can only be processed for orders in the confirmed status.', Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function orderAlreadyPaid(): self
    {
        return new self('This order has already been paid successfully.');
    }

    public static function orderHasPayments(): self
    {
        return new self('Orders with associated payments cannot be deleted.');
    }

    public static function itemsLocked(): self
    {
        return new self('Order items cannot be changed once payments exist for the order.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status);
    }
}
