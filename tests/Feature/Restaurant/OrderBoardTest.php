<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

it('lists the restaurant orders for the owner', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    Order::factory()->count(3)->for($restaurant)->create();
    Order::factory()->count(2)->create(); // other restaurants

    $this->getJson("/api/restaurants/{$restaurant->id}/orders")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters the orders board by status', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    Order::factory()->count(2)->for($restaurant)->create(['status' => OrderStatus::Confirmed]);
    Order::factory()->for($restaurant)->create(['status' => OrderStatus::Served]);

    $this->getJson("/api/restaurants/{$restaurant->id}/orders?filter[status]=confirmed")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('forbids a non-owner from viewing the orders board', function () {
    actingAsRestaurateur();
    $restaurant = Restaurant::factory()->create(); // another owner

    $this->getJson("/api/restaurants/{$restaurant->id}/orders")->assertForbidden();
});

it('requires authentication to view the orders board', function () {
    $restaurant = Restaurant::factory()->create();

    $this->getJson("/api/restaurants/{$restaurant->id}/orders")->assertUnauthorized();
});
