<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\ListOrdersRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(ListOrdersRequest $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->status($request->validated('status'))
            ->with('items')
            ->withCount('payments')
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orders->create($request->user(), $request->validated());

        return (new OrderResource($order))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load('items')->loadCount('payments'));
    }

    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        Gate::authorize('update', $order);

        return new OrderResource($this->orders->update($order, $request->validated()));
    }

    public function destroy(Order $order): JsonResponse
    {
        Gate::authorize('delete', $order);

        $this->orders->delete($order);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
