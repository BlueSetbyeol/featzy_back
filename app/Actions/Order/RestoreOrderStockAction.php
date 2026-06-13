<?php

namespace App\Actions\Order;

use App\Models\MenuItem;
use App\Models\MenuItemOption;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RestoreOrderStockAction
{
    /**
     * Return a placed order's stock to inventory, mirroring PlaceOrderAction.
     * Idempotent via orders.stock_restored_at (re-checked under a row lock), so a
     * double cancel never restores twice. A never-placed order is a no-op.
     */
    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->placed_at === null || $locked->stock_restored_at !== null) {
                return;
            }

            $items = $locked->items()->with('options')->get();

            $menuItemNeeds = $items
                ->groupBy('menu_item_id')
                ->map(fn ($group): int => (int) $group->sum('quantity'))
                ->sortKeys();

            foreach ($menuItemNeeds as $menuItemId => $needed) {
                // withTrashed so a dish soft-deleted after placement still has its
                // decremented stock returned (mirrors PlaceOrderAction).
                $menuItem = MenuItem::withTrashed()->whereKey($menuItemId)->lockForUpdate()->first();

                if ($menuItem !== null && $menuItem->stock_quantity !== null) {
                    $menuItem->increment('stock_quantity', $needed);
                }
            }

            foreach ($this->optionNeeds($items) as $optionId => $needed) {
                $option = MenuItemOption::query()->whereKey($optionId)->lockForUpdate()->first();

                if ($option !== null && $option->stock_quantity !== null) {
                    $option->increment('stock_quantity', $needed);
                }
            }

            $locked->update(['stock_restored_at' => now()]);
        });
    }

    /**
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
