<?php

use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationParticipant;
use App\Models\Restaurant;
use App\Models\User;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

it('opens the order for a participant', function () {
    $organizer = actingAsClient();
    $restaurant = Restaurant::factory()->published()->create(['accepts_preorders' => true]);
    $reservation = Reservation::factory()->for($restaurant)->for($organizer, 'organizer')->create([
        'is_preorder' => true,
        'status' => ReservationStatus::Confirmed,
    ]);
    ReservationParticipant::factory()->for($reservation)->for($organizer)->organizer()->create();

    $this->postJson("/api/reservations/{$reservation->id}/order")
        ->assertCreated()
        ->assertJsonPath('data.reservation_id', $reservation->id)
        ->assertJsonPath('data.status', OrderStatus::Pending->value)
        ->assertJsonPath('data.items_total', 0);

    $this->assertDatabaseHas('orders', [
        'reservation_id' => $reservation->id,
        'restaurant_id' => $restaurant->id,
    ]);
});

it('is idempotent and returns the existing order', function () {
    $organizer = actingAsClient();
    ['reservation' => $reservation, 'order' => $order] = preorderContext($organizer);

    $this->postJson("/api/reservations/{$reservation->id}/order")
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);

    $this->assertDatabaseCount('orders', 1);
});

it('rejects opening an order on a non pre-order reservation', function () {
    $organizer = actingAsClient();
    $restaurant = Restaurant::factory()->published()->create(['accepts_preorders' => true]);
    $reservation = Reservation::factory()->for($restaurant)->for($organizer, 'organizer')->create([
        'is_preorder' => false,
        'status' => ReservationStatus::Confirmed,
    ]);
    ReservationParticipant::factory()->for($reservation)->for($organizer)->organizer()->create();

    $this->postJson("/api/reservations/{$reservation->id}/order")
        ->assertStatus(422)
        ->assertJsonPath('code', 'PREORDERS_NOT_ACCEPTED');
});

it('rejects opening an order on a cancelled reservation', function () {
    $organizer = actingAsClient();
    $restaurant = Restaurant::factory()->published()->create(['accepts_preorders' => true]);
    $reservation = Reservation::factory()->for($restaurant)->for($organizer, 'organizer')->create([
        'is_preorder' => true,
        'status' => ReservationStatus::Cancelled,
    ]);
    ReservationParticipant::factory()->for($reservation)->for($organizer)->organizer()->create();

    $this->postJson("/api/reservations/{$reservation->id}/order")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');
});

it('forbids a user who is not a participant from opening the order', function () {
    ['reservation' => $reservation] = preorderContext(User::factory()->client()->create());

    actingAsClient();

    $this->postJson("/api/reservations/{$reservation->id}/order")->assertForbidden();
});

it('requires authentication to open the order', function () {
    ['reservation' => $reservation] = preorderContext(User::factory()->client()->create());

    $this->postJson("/api/reservations/{$reservation->id}/order")->assertUnauthorized();
});

it('lets a participant view the order', function () {
    $organizer = actingAsClient();
    ['order' => $order] = preorderContext($organizer);

    $this->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);
});

it('lets the restaurant owner view the order', function () {
    ['restaurant' => $restaurant, 'order' => $order] = preorderContext(User::factory()->client()->create());

    $this->actingAs($restaurant->owner);

    $this->getJson("/api/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);
});

it('forbids an unrelated user from viewing the order', function () {
    ['order' => $order] = preorderContext(User::factory()->client()->create());

    actingAsClient();

    $this->getJson("/api/orders/{$order->id}")->assertForbidden();
});

it('requires authentication to view the order', function () {
    ['order' => $order] = preorderContext(User::factory()->client()->create());

    $this->getJson("/api/orders/{$order->id}")->assertUnauthorized();
});
