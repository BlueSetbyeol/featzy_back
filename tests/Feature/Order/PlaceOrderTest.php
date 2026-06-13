<?php

use App\Enums\OrderStatus;
use App\Events\Order\OrderPlaced;
use App\Models\MenuItemOption;
use App\Models\MenuItemOptionGroup;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

it('places the order and decrements menu item stock', function () {
    Event::fake([OrderPlaced::class]);

    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['price' => 1000, 'stock_quantity' => 10]);
    OrderItem::factory()->for($order)->for($participant, 'participant')->for($item, 'menuItem')->create([
        'name_snapshot' => $item->name, 'quantity' => 3, 'unit_price_snapshot' => 1000, 'options_total_snapshot' => 0,
    ]);

    $this->postJson("/api/orders/{$order->id}/place")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Confirmed->value)
        ->assertJsonPath('data.placed_at', fn ($value) => $value !== null);

    $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'stock_quantity' => 7]);

    Event::assertDispatched(OrderPlaced::class);
});

it('places when stock exactly covers the order', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['stock_quantity' => 3]);
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create();
    $option = MenuItemOption::factory()->for($group, 'group')->create(['stock_quantity' => 3]);

    $orderItem = OrderItem::factory()->for($order)->for($participant, 'participant')->for($item, 'menuItem')->create([
        'name_snapshot' => $item->name, 'quantity' => 3, 'unit_price_snapshot' => 0, 'options_total_snapshot' => 0,
    ]);
    OrderItemOption::factory()->for($orderItem)->for($option, 'menuItemOption')->create([
        'quantity' => 1, 'label_snapshot' => $option->name, 'price_delta_snapshot' => $option->price_delta,
    ]);

    $this->postJson("/api/orders/{$order->id}/place")->assertOk();

    $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'stock_quantity' => 0]);
    $this->assertDatabaseHas('menu_item_options', ['id' => $option->id, 'stock_quantity' => 0]);
});

it('decrements option stock by item quantity times option quantity', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['stock_quantity' => null]);
    $group = MenuItemOptionGroup::factory()->for($item, 'menuItem')->create();
    $option = MenuItemOption::factory()->for($group, 'group')->create(['stock_quantity' => 5]);

    $orderItem = OrderItem::factory()->for($order)->for($participant, 'participant')->for($item, 'menuItem')->create([
        'name_snapshot' => $item->name, 'quantity' => 2, 'unit_price_snapshot' => 0, 'options_total_snapshot' => 0,
    ]);
    OrderItemOption::factory()->for($orderItem)->for($option, 'menuItemOption')->create([
        'quantity' => 1, 'label_snapshot' => $option->name, 'price_delta_snapshot' => $option->price_delta,
    ]);

    $this->postJson("/api/orders/{$order->id}/place")->assertOk();

    $this->assertDatabaseHas('menu_item_options', ['id' => $option->id, 'stock_quantity' => 3]);
});

it('leaves untracked (null) stock untouched', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['stock_quantity' => null]);
    OrderItem::factory()->for($order)->for($participant, 'participant')->for($item, 'menuItem')->create([
        'name_snapshot' => $item->name, 'quantity' => 3, 'unit_price_snapshot' => 0, 'options_total_snapshot' => 0,
    ]);

    $this->postJson("/api/orders/{$order->id}/place")->assertOk();

    expect($item->refresh()->stock_quantity)->toBeNull();
});

it('rejects placing when stock is insufficient and changes nothing', function () {
    $organizer = actingAsClient();
    ['restaurant' => $restaurant, 'order' => $order, 'participant' => $participant] = preorderContext($organizer);
    $item = menuItemFor($restaurant, ['stock_quantity' => 2]);
    OrderItem::factory()->for($order)->for($participant, 'participant')->for($item, 'menuItem')->create([
        'name_snapshot' => $item->name, 'quantity' => 5, 'unit_price_snapshot' => 0, 'options_total_snapshot' => 0,
    ]);

    $this->postJson("/api/orders/{$order->id}/place")
        ->assertStatus(409)
        ->assertJsonPath('code', 'INSUFFICIENT_STOCK');

    $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'stock_quantity' => 2]);
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Pending->value, 'placed_at' => null]);
});

it('forbids a non-organizer from placing the order', function () {
    ['order' => $order] = preorderContext(User::factory()->client()->create());

    actingAsClient();

    $this->postJson("/api/orders/{$order->id}/place")->assertForbidden();
});

it('rejects placing an order that is already placed', function () {
    $organizer = actingAsClient();
    ['order' => $order] = preorderContext($organizer);
    $order->update(['status' => OrderStatus::Confirmed, 'placed_at' => now()]);

    $this->postJson("/api/orders/{$order->id}/place")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
});

it('requires authentication to place an order', function () {
    ['order' => $order] = preorderContext(User::factory()->client()->create());

    $this->postJson("/api/orders/{$order->id}/place")->assertUnauthorized();
});
