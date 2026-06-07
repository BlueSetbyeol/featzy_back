<?php

use App\Enums\OrderStatus;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReservationParticipant;
use App\Models\User;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

/**
 * Attach a pending order item owned by $participant to $order for $menuItemPrice.
 */
function pendingOrderItem(Order $order, ReservationParticipant $participant, MenuItem $item, int $quantity = 2): OrderItem
{
    $orderItem = OrderItem::factory()->for($order)->for($participant, 'participant')->for($item, 'menuItem')->create([
        'name_snapshot' => $item->name,
        'quantity' => $quantity,
        'unit_price_snapshot' => $item->price,
        'options_total_snapshot' => 0,
    ]);

    $order->recalculateItemsTotal();

    return $orderItem;
}

it('updates an item quantity and recomputes the total', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 1000]);
    $orderItem = pendingOrderItem($order, $participant, $item, quantity: 2);

    $this->patchJson("/api/order-items/{$orderItem->id}", ['quantity' => 3])
        ->assertOk()
        ->assertJsonPath('data.quantity', 3)
        ->assertJsonPath('data.line_total', 3000);

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'items_total' => 3000]);
});

it('deletes an item and recomputes the total', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 1000]);
    $kept = pendingOrderItem($order, $participant, $item, quantity: 1);
    $removed = pendingOrderItem($order, $participant, $item, quantity: 2);

    $this->deleteJson("/api/order-items/{$removed->id}")->assertNoContent();

    $this->assertDatabaseMissing('order_items', ['id' => $removed->id]);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'items_total' => 1000]);
});

it('forbids modifying an item the user does not own', function () {
    $organizer = User::factory()->client()->create();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 1000]);
    $orderItem = pendingOrderItem($order, $participant, $item);

    actingAsClient();

    $this->patchJson("/api/order-items/{$orderItem->id}", ['quantity' => 3])->assertForbidden();
    $this->deleteJson("/api/order-items/{$orderItem->id}")->assertForbidden();
});

it('rejects modifying an item once the order is placed', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 1000]);
    $orderItem = pendingOrderItem($order, $participant, $item);
    $order->update(['status' => OrderStatus::Confirmed, 'placed_at' => now()]);

    $this->patchJson("/api/order-items/{$orderItem->id}", ['quantity' => 3])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');

    $this->deleteJson("/api/order-items/{$orderItem->id}")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
});

it('requires authentication to modify an item', function () {
    $organizer = User::factory()->client()->create();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 1000]);
    $orderItem = pendingOrderItem($order, $participant, $item);

    $this->patchJson("/api/order-items/{$orderItem->id}", ['quantity' => 3])->assertUnauthorized();
});
