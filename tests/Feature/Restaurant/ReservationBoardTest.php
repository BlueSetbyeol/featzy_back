<?php

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

it('lists the restaurant reservations for the owner', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    Reservation::factory()->count(3)->for($restaurant)->create();
    Reservation::factory()->count(2)->create(); // other restaurants

    $this->getJson("/api/restaurants/{$restaurant->id}/reservations")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters the board by status', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    Reservation::factory()->count(2)->for($restaurant)->create(['status' => ReservationStatus::Confirmed]);
    Reservation::factory()->for($restaurant)->create(['status' => ReservationStatus::Cancelled]);

    $this->getJson("/api/restaurants/{$restaurant->id}/reservations?filter[status]=confirmed")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters the board by reservation date', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $target = CarbonImmutable::today()->addDays(3)->toDateString();
    Reservation::factory()->count(2)->for($restaurant)->create(['reservation_date' => $target]);
    Reservation::factory()->for($restaurant)->create(['reservation_date' => CarbonImmutable::today()->addDays(7)->toDateString()]);

    $this->getJson("/api/restaurants/{$restaurant->id}/reservations?filter[reservation_date]={$target}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('forbids a non-owner from viewing the board', function () {
    actingAsRestaurateur();
    $restaurant = Restaurant::factory()->create(); // another owner

    $this->getJson("/api/restaurants/{$restaurant->id}/reservations")->assertForbidden();
});

it('requires authentication to view the reservation board', function () {
    $restaurant = Restaurant::factory()->create();

    $this->getJson("/api/restaurants/{$restaurant->id}/reservations")->assertUnauthorized();
});
