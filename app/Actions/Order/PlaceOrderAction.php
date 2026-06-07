<?php

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Events\Order\OrderPlaced;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\Order\InsufficientStockException;
use App\Models\MenuItem;
use App\Models\MenuItemOption;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlaceOrderAction
{
    /**
     * Finalize a pending order: decrement menu item and option stock atomically,
     * then mark it placed. Each tracked row is locked (lockForUpdate, ordered by
     * id to avoid deadlocks) and checked before decrementing; null stock means
     * untracked (unlimited) and is left alone. Any shortfall aborts the whole
     * transaction with a 409.
     */
    public function handle(Order $order): Order
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::Pending) {
                throw new InvalidStatusTransitionException('Only a pending order can be placed.');
            }

            $items = $locked->items()->with('options')->get();

            $menuItemNeeds = $items
                ->groupBy('menu_item_id')
                ->map(fn ($group): int => (int) $group->sum('quantity'))
                ->sortKeys();

            foreach ($menuItemNeeds as $menuItemId => $needed) {
                // withTrashed: the order item pins the menu item by id, so a dish
                // soft-deleted after ordering must still be decremented/restored.
                $menuItem = MenuItem::withTrashed()->whereKey($menuItemId)->lockForUpdate()->first();

                if ($menuItem !== null && $menuItem->stock_quantity !== null) {
                    if ($menuItem->stock_quantity < $needed) {
                        throw new InsufficientStockException;
                    }

                    $menuItem->decrement('stock_quantity', $needed);
                }
            }

            foreach ($this->optionNeeds($items) as $optionId => $needed) {
                $option = MenuItemOption::query()->whereKey($optionId)->lockForUpdate()->first();

                if ($option !== null && $option->stock_quantity !== null) {
                    if ($option->stock_quantity < $needed) {
                        throw new InsufficientStockException;
                    }

                    $option->decrement('stock_quantity', $needed);
                }
            }

            $locked->update([
                'status' => OrderStatus::Confirmed->value,
                'placed_at' => now(),
            ]);
        });

        $order->refresh();

        OrderPlaced::dispatch($order);

        return $order->load(['items.options']);
    }

    /**
     * Total quantity needed per option id (order item quantity × option quantity),
     * keyed by option id and sorted for a stable lock order. Snapshot rows whose
     * source option was deleted (null id) are skipped.
     *
     * @param  Collection<int, OrderItem>  $items
     * @return array<int, int>
     */
    private function optionNeeds($items): array
    {
        $needs = [];

        foreach ($items as $item) {
            foreach ($item->options as $option) {
                if ($option->menu_item_option_id === null) {
                    continue;
                }

                $needs[$option->menu_item_option_id] = ($needs[$option->menu_item_option_id] ?? 0)
                    + $item->quantity * $option->quantity;
            }
        }

        ksort($needs);

        return $needs;
    }
}
