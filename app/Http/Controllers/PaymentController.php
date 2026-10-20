<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\ListPaymentsRequest;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
    }

    public function index(ListPaymentsRequest $request): AnonymousResourceCollection
    {
        $payments = Payment::query()
            ->ownedBy($request->user())
            ->when($request->validated('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->validated('method'), fn ($q, $method) => $q->where('method', $method))
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PaymentResource::collection($payments);
    }

    public function show(Payment $payment): PaymentResource
    {
        Gate::authorize('view', $payment->order);

        return new PaymentResource($payment);
    }

    public function forOrder(ListPaymentsRequest $request, Order $order): AnonymousResourceCollection
    {
        Gate::authorize('view', $order);

        $payments = $order->payments()
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return PaymentResource::collection($payments);
    }

    public function store(ProcessPaymentRequest $request, Order $order): JsonResponse
    {
        Gate::authorize('pay', $order);

        $payment = $this->payments->process(
            $order,
            $request->validated('method'),
            $request->validated('payment_details'),
        );

        return (new PaymentResource($payment))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function methods(PaymentGatewayManager $gateways): JsonResponse
    {
        return response()->json(['data' => $gateways->supportedMethods()]);
    }
}
