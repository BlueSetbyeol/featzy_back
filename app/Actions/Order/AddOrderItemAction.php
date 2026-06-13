<?php

namespace App\Actions\Order;

use App\Data\Order\AddOrderItemData;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\MenuItem;
use App\Models\MenuItemOption;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddOrderItemAction
{
    /**
     * Add one participant's line to a pending order, freezing the menu item and
     * option prices as snapshots. line_total is a generated STORED column, so it
     * is never written — only read back. Stock is not touched here; it is taken
     * atomically when the order is placed.
     */
    public function handle(Order $order, AddOrderItemData $data): OrderItem
    {
        if ($order->status !== OrderStatus::Pending) {
            throw new InvalidStatusTransitionException('Items can only be added while the order is pending.');
        }

        $menuItem = MenuItem::query()
            ->with('optionGroups.options')
            ->findOrFail($data->menu_item_id);

        $selectedOptions = $this->resolveSelectedOptions($menuItem, $data->options);

        $optionsTotal = $selectedOptions->sum(
            fn (array $selection): int => $selection['option']->price_delta * $selection['quantity'],
        );

        // line_total is an UNSIGNED generated column; a discount deeper than the
        // base price would overflow the unsigned arithmetic on INSERT (500) before
        // the CHECK could fire, so guard the floor in code as a 422.
        if ($menuItem->price + $optionsTotal < 0) {
            throw ValidationException::withMessages([
                'options' => ['The selected options reduce the price below zero.'],
            ]);
        }

        $orderItem = DB::transaction(function () use ($order, $data, $menuItem, $selectedOptions, $optionsTotal): OrderItem {
            $orderItem = $order->items()->create([
                'reservation_participant_id' => $data->reservation_participant_id,
                'menu_item_id' => $menuItem->id,
                'name_snapshot' => $menuItem->name,
                'quantity' => $data->quantity,
                'unit_price_snapshot' => $menuItem->price,
                'options_total_snapshot' => $optionsTotal,
                'status' => OrderItemStatus::Pending->value,
                'notes' => $data->notes,
            ]);

            foreach ($selectedOptions as $selection) {
                $orderItem->options()->create([
                    'menu_item_option_id' => $selection['option']->id,
                    'label_snapshot' => $selection['option']->name,
                    'price_delta_snapshot' => $selection['option']->price_delta,
                    'quantity' => $selection['quantity'],
                ]);
            }

            $order->recalculateItemsTotal();

            return $orderItem;
        });

        // Re-read so the generated STORED line_total is populated on the instance.
        return $orderItem->fresh('options');
    }

    /**
     * Validate the option selection against the item's groups (membership,
     * availability, min/max/required) and return the resolved options with
     * quantities.
     *
     * @param  array<int, array{id: int, quantity?: int}>  $options
     * @return Collection<int, array{option: MenuItemOption, quantity: int}>
     */
    private function resolveSelectedOptions(MenuItem $menuItem, array $options): Collection
    {
        $validOptions = $menuItem->optionGroups->flatMap->options->keyBy('id');

        $selections = collect($options)->map(function (array $selection) use ($validOptions) {
            $option = $validOptions->get($selection['id']);

            if ($option === null) {
                throw ValidationException::withMessages([
                    'options' => ['One or more selected options do not belong to this item.'],
                ]);
            }

            if (! $option->is_available) {
                throw ValidationException::withMessages([
                    'options' => ['One or more selected options are unavailable.'],
                ]);
            }

            return ['option' => $option, 'quantity' => $selection['quantity'] ?? 1];
        });

        foreach ($menuItem->optionGroups as $group) {
            $count = $selections->filter(
                fn (array $selection): bool => $selection['option']->option_group_id === $group->id,
            )->count();

            $withinMin = $count >= $group->min_select;
            $withinMax = $group->max_select === null || $count <= $group->max_select;
            $requiredMet = ! $group->is_required || $count >= 1;

            if (! $withinMin || ! $withinMax || ! $requiredMet) {
                throw ValidationException::withMessages([
                    'options' => ["The selection for \"{$group->name}\" is invalid."],
                ]);
            }
        }

        return $selections;
    }
}
