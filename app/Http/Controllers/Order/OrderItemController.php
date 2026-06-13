<?php

namespace App\Http\Controllers\Order;

use App\Actions\Order\AddOrderItemAction;
use App\Data\Order\AddOrderItemData;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddOrderItemRequest;
use App\Http\Requests\Order\UpdateOrderItemRequest;
use App\Http\Resources\OrderItemResource;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class OrderItemController extends Controller
{
    public function store(AddOrderItemRequest $request, Order $order, AddOrderItemAction $action): JsonResponse
    {
        $orderItem = $action->handle($order, AddOrderItemData::from($request->validated()));

        return OrderItemResource::make($orderItem)
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function update(UpdateOrderItemRequest $request, OrderItem $orderItem): OrderItemResource
    {
        DB::transaction(function () use ($request, $orderItem): void {
            $order = $this->lockPendingOrder($orderItem);

            $orderItem->update($request->validated());
            $order->recalculateItemsTotal();
        });

        return OrderItemResource::make($orderItem->fresh('options'));
    }

    public function destroy(OrderItem $orderItem): Response
    {
        DB::transaction(function () use ($orderItem): void {
            $order = $this->lockPendingOrder($orderItem);

            $orderItem->delete();
            $order->recalculateItemsTotal();
        });

        return response()->noContent();
    }

    /**
     * Lock the parent order and assert it is still pending. Locking serializes
     * the edit against a concurrent place/cancel (which also lock the order row),
     * closing the read-then-write window on the "editable only while pending" rule.
     */
    private function lockPendingOrder(OrderItem $orderItem): Order
    {
        $order = $orderItem->order()->lockForUpdate()->firstOrFail();

        if ($order->status !== OrderStatus::Pending) {
            throw new InvalidStatusTransitionException('The order has already been placed and can no longer be modified.');
        }

        return $order;
    }
}
