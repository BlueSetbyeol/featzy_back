<?php

use App\Enums\OrderStatus;
use App\Models\MenuItemOption;
use App\Models\MenuItemOptionGroup;
use App\Models\ReservationParticipant;
use App\Models\Restaurant;
use App\Models\User;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

it('adds a line and snapshots the menu item price', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['name' => 'Burger', 'price' => 1200, 'stock_quantity' => null]);

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 2,
    ])
        ->assertCreated()
        ->assertJsonPath('data.name_snapshot', 'Burger')
        ->assertJsonPath('data.unit_price_snapshot', 1200)
        ->assertJsonPath('data.options_total_snapshot', 0)
        ->assertJsonPath('data.line_total', 2400)
        ->assertJsonPath('data.quantity', 2);

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'items_total' => 2400]);
});

it('snapshots selected options and adds them to the line total', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 1000]);
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create([
        'min_select' => 0, 'max_select' => 2, 'is_required' => false,
    ]);
    $option = MenuItemOption::factory()->for($group, 'group')->create([
        'name' => 'Extra cheese', 'price_delta' => 200, 'is_available' => true, 'stock_quantity' => null,
    ]);

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
        'options' => [['id' => $option->id, 'quantity' => 1]],
    ])
        ->assertCreated()
        ->assertJsonPath('data.options_total_snapshot', 200)
        ->assertJsonPath('data.line_total', 1200)
        ->assertJsonPath('data.options.0.label_snapshot', 'Extra cheese')
        ->assertJsonPath('data.options.0.price_delta_snapshot', 200);
});

it('freezes the snapshot against later menu price changes', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 1000]);

    $orderItemId = $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
    ])->assertCreated()->json('data.id');

    $item->update(['price' => 9999]);

    $this->assertDatabaseHas('order_items', [
        'id' => $orderItemId,
        'unit_price_snapshot' => 1000,
        'line_total' => 1000,
    ]);
});

it('rejects options that discount the price below zero', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 100]);
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create([
        'min_select' => 0, 'max_select' => 1, 'is_required' => false,
    ]);
    $option = MenuItemOption::factory()->for($group, 'group')->create([
        'price_delta' => -500, 'is_available' => true, 'stock_quantity' => null,
    ]);

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
        'options' => [['id' => $option->id]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('options');

    $this->assertDatabaseCount('order_items', 0);
});

it('rejects an unavailable option', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant);
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create([
        'min_select' => 0, 'max_select' => 1, 'is_required' => false,
    ]);
    $option = MenuItemOption::factory()->for($group, 'group')->create(['is_available' => false]);

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
        'options' => [['id' => $option->id]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('options');
});

it('rejects a selection that omits a required option group', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant);
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create([
        'min_select' => 1, 'max_select' => 1, 'is_required' => true,
    ]);
    MenuItemOption::factory()->for($group, 'group')->create(['is_available' => true]);

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
        'options' => [],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('options');
});

it('rejects exceeding a group max_select', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant);
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create([
        'min_select' => 0, 'max_select' => 1, 'is_required' => false,
    ]);
    $a = MenuItemOption::factory()->for($group, 'group')->create(['is_available' => true]);
    $b = MenuItemOption::factory()->for($group, 'group')->create(['is_available' => true]);

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
        'options' => [['id' => $a->id], ['id' => $b->id]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('options');
});

it('rejects an option that does not belong to the item', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant);
    $foreignOption = MenuItemOption::factory()->create(['is_available' => true]); // belongs to another item

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
        'options' => [['id' => $foreignOption->id]],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('options');
});

it('rejects a menu item from another restaurant', function () {
    $organizer = actingAsClient();
    ['order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $foreignItem = menuItemFor(Restaurant::factory()->published()->create());

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $foreignItem->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('menu_item_id');
});

it('rejects ordering for a participant that is not the user', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'reservation' => $reservation] = preorderContext($organizer);
    $item = menuItemFor($restaurant);
    $otherParticipant = ReservationParticipant::factory()->for($reservation)->create(); // a different user

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $otherParticipant->id,
        'quantity' => 1,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('reservation_participant_id');
});

it('rejects adding to an order that is already placed', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $order->update(['status' => OrderStatus::Confirmed, 'placed_at' => now()]);
    $item = menuItemFor($restaurant);

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => $item->id,
        'reservation_participant_id' => $participant->id,
        'quantity' => 1,
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
});

it('forbids a non-participant from adding items', function () {
    ['order' => $order] = preorderContext(User::factory()->client()->create());

    actingAsClient();

    $this->postJson("/api/orders/{$order->id}/items", [
        'menu_item_id' => 1,
        'reservation_participant_id' => 1,
        'quantity' => 1,
    ])->assertForbidden();
});

it('requires authentication to add items', function () {
    ['order' => $order] = preorderContext(User::factory()->client()->create());

    $this->postJson("/api/orders/{$order->id}/items", [])->assertUnauthorized();
});
