<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    /**
     * Run the business rules, delegate to the gateway strategy for the method, and persist the outcome.
     *
     * @param  array<string, mixed>  $details  Validated gateway-specific payload.
     */
    public function process(Order $order, string $method, array $details): Payment
    {
        return DB::transaction(function () use ($order, $method, $details) {
            // Lock the order row so two concurrent requests cannot both pay it.
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->isConfirmed()) {
                throw BusinessRuleException::orderNotConfirmed();
            }

            if ($order->isPaid()) {
                throw BusinessRuleException::orderAlreadyPaid();
            }

            $gateway = $this->gateways->gateway($method);

            $payment = $order->payments()->create([
                'method' => $method,
                'status' => PaymentStatus::Pending,
                'amount' => $order->total,
            ]);

            $result = $gateway->charge($order, $details);

            $payment->update([
                'status' => $result->status,
                'transaction_reference' => $result->transactionReference,
                'failure_reason' => $result->failureReason,
                'meta' => $result->meta,
            ]);

            return $payment;
        });
    }
}
