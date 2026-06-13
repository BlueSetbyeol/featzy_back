<?php

namespace App\Http\Controllers\Order;

use App\Actions\Order\PlaceOrderAction;
use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\Order\PreordersNotAcceptedException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class OrderController extends Controller
{
    /**
     * Open (or return) the single pre-order attached to the reservation.
     */
    public function store(Reservation $reservation): JsonResponse
    {
        if (! $reservation->is_preorder) {
            throw new PreordersNotAcceptedException('This reservation is not a pre-order.');
        }

        if ($reservation->status !== ReservationStatus::Confirmed) {
            throw new InvalidStatusTransitionException('An order can only be opened on a confirmed reservation.');
        }

        $order = Order::query()->firstOrCreate(
            ['reservation_id' => $reservation->id],
            [
                'restaurant_id' => $reservation->restaurant_id,
                'status' => OrderStatus::Pending->value,
                'items_total' => 0,
            ],
        );

        return OrderResource::make($order->load('items.options'))
            ->response()
            ->setStatusCode($order->wasRecentlyCreated ? HttpResponse::HTTP_CREATED : HttpResponse::HTTP_OK);
    }

    public function show(Order $order): OrderResource
    {
        return OrderResource::make($order->load('items.options'));
    }

    public function place(Order $order, PlaceOrderAction $action): OrderResource
    {
        return OrderResource::make($action->handle($order));
    }
}
