<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class TransitionOrderStatusAction
{
    public function __construct(private readonly RestoreOrderStockAction $restoreOrderStock) {}

    /**
     * Move an order along the kitchen lifecycle (prepare / serve / cancel) via a
     * conditional UPDATE constrained to the legal source statuses. Cancelling any
     * not-yet-served order returns its (placed) stock to inventory.
     */
    public function handle(Order $order, OrderStatus $to): Order
    {
        $allowed = $this->allowedSources($to);

        if (! in_array($order->status, $allowed, true)) {
            throw InvalidStatusTransitionException::between($order->status->value, $to->value);
        }

        DB::transaction(function () use ($order, $to, $allowed): void {
            $transitioned = Order::query()
                ->whereKey($order->id)
                ->whereIn('status', array_map(fn (OrderStatus $status): string => $status->value, $allowed))
                ->update(['status' => $to->value]);

            if ($transitioned === 0) {
                throw InvalidStatusTransitionException::between($order->status->value, $to->value);
            }

            if ($to === OrderStatus::Cancelled) {
                $this->restoreOrderStock->handle($order);
            }
        });

        $order->refresh();

        return $order->load(['items.options']);
    }

    /**
     * @return array<int, OrderStatus>
     */
    private function allowedSources(OrderStatus $to): array
    {
        return match ($to) {
            OrderStatus::Preparing => [OrderStatus::Confirmed],
            OrderStatus::Served => [OrderStatus::Preparing],
            OrderStatus::Cancelled => [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Preparing],
            default => [],
        };
    }
}
