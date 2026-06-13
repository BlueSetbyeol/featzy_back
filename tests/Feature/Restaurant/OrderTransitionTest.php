<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

it('moves a confirmed order to preparing', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $order = Order::factory()->for($restaurant)->create(['status' => OrderStatus::Confirmed, 'placed_at' => now()]);

    $this->postJson("/api/orders/{$order->id}/prepare")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Preparing->value);
});

it('serves a preparing order', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $order = Order::factory()->for($restaurant)->create(['status' => OrderStatus::Preparing, 'placed_at' => now()]);

    $this->postJson("/api/orders/{$order->id}/serve")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Served->value);
});

it('cancels an order and restores its stock', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $item = menuItemFor($restaurant, ['stock_quantity' => 7]); // already decremented at place
    $order = Order::factory()->for($restaurant)->create([
        'status' => OrderStatus::Confirmed, 'placed_at' => now(), 'stock_restored_at' => null,
    ]);
    OrderItem::factory()->for($order)->for($item, 'menuItem')->create([
        'name_snapshot' => $item->name, 'quantity' => 3, 'unit_price_snapshot' => 0, 'options_total_snapshot' => 0,
    ]);

    $this->postJson("/api/orders/{$order->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

    $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'stock_quantity' => 10]);
    expect($order->refresh()->stock_restored_at)->not->toBeNull();
});

it('cancels a pending order without restoring stock', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $item = menuItemFor($restaurant, ['stock_quantity' => 10]);
    $order = Order::factory()->for($restaurant)->create([
        'status' => OrderStatus::Pending, 'placed_at' => null, 'stock_restored_at' => null,
    ]);
    OrderItem::factory()->for($order)->for($item, 'menuItem')->create([
        'name_snapshot' => $item->name, 'quantity' => 3, 'unit_price_snapshot' => 0, 'options_total_snapshot' => 0,
    ]);

    $this->postJson("/api/orders/{$order->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

    $this->assertDatabaseHas('menu_items', ['id' => $item->id, 'stock_quantity' => 10]);
    expect($order->refresh()->stock_restored_at)->toBeNull();
});

it('cancels a preparing order', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $order = Order::factory()->for($restaurant)->create(['status' => OrderStatus::Preparing, 'placed_at' => now()]);

    $this->postJson("/api/orders/{$order->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Cancelled->value);
});

it('rejects an illegal order transition', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $order = Order::factory()->for($restaurant)->create(['status' => OrderStatus::Confirmed, 'placed_at' => now()]);

    // serve requires a preparing order
    $this->postJson("/api/orders/{$order->id}/serve")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');

    $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => OrderStatus::Confirmed->value]);
});

it('forbids a non-owner from managing an order', function () {
    actingAsRestaurateur();
    $order = Order::factory()->create(); // belongs to another restaurant

    $this->postJson("/api/orders/{$order->id}/prepare")->assertForbidden();
});

it('requires authentication to manage an order', function () {
    $order = Order::factory()->create();

    $this->postJson("/api/orders/{$order->id}/prepare")->assertUnauthorized();
});
