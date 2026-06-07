<?php

use App\Enums\ReservationStatus;
use App\Enums\ServiceType;
use App\Models\Reservation;
use App\Models\Restaurant;
use App\Models\ServiceAvailability;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
    CarbonImmutable::setTestNow('2026-06-15 09:00:00');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('seats a confirmed reservation', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $reservation = Reservation::factory()->for($restaurant)->create(['status' => ReservationStatus::Confirmed]);

    $this->postJson("/api/reservations/{$reservation->id}/seat")
        ->assertOk()
        ->assertJsonPath('data.status', ReservationStatus::Seated->value)
        ->assertJsonPath('data.seated_at', fn ($value) => $value !== null);
});

it('completes a seated reservation', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $reservation = Reservation::factory()->for($restaurant)->create(['status' => ReservationStatus::Seated]);

    $this->postJson("/api/reservations/{$reservation->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', ReservationStatus::Completed->value)
        ->assertJsonPath('data.completed_at', fn ($value) => $value !== null);
});

it('marks a reservation as no-show without restoring capacity', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $slot = ServiceAvailability::factory()->for($restaurant)->create(['capacity' => 40, 'booked_seats' => 4]);
    $reservation = Reservation::factory()->for($restaurant)->create([
        'service_availability_id' => $slot->id,
        'status' => ReservationStatus::Confirmed,
        'party_size' => 4,
    ]);

    $this->postJson("/api/reservations/{$reservation->id}/no-show")
        ->assertOk()
        ->assertJsonPath('data.status', ReservationStatus::NoShow->value);

    $this->assertDatabaseHas('service_availabilities', ['id' => $slot->id, 'booked_seats' => 4]);
});

it('marks a seated reservation as no-show', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $slot = ServiceAvailability::factory()->for($restaurant)->create(['capacity' => 40, 'booked_seats' => 4]);
    $reservation = Reservation::factory()->for($restaurant)->create([
        'service_availability_id' => $slot->id,
        'status' => ReservationStatus::Seated,
        'party_size' => 4,
    ]);

    $this->postJson("/api/reservations/{$reservation->id}/no-show")
        ->assertOk()
        ->assertJsonPath('data.status', ReservationStatus::NoShow->value);

    $this->assertDatabaseHas('service_availabilities', ['id' => $slot->id, 'booked_seats' => 4]);
});

it('rejects an illegal reservation transition', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $reservation = Reservation::factory()->for($restaurant)->create(['status' => ReservationStatus::Confirmed]);

    // complete requires a seated reservation
    $this->postJson("/api/reservations/{$reservation->id}/complete")
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');

    $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => ReservationStatus::Confirmed->value]);
});

it('forbids a non-owner from managing a reservation', function () {
    actingAsRestaurateur();
    $reservation = Reservation::factory()->create(); // belongs to another restaurant

    $this->postJson("/api/reservations/{$reservation->id}/seat")->assertForbidden();
});

it('requires authentication to manage a reservation', function () {
    $reservation = Reservation::factory()->create();

    $this->postJson("/api/reservations/{$reservation->id}/seat")->assertUnauthorized();
});

it('lets the restaurant owner cancel past the deadline and restores capacity', function () {
    $owner = actingAsRestaurateur();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create(['cancellation_deadline_hours' => 24]);
    $slot = ServiceAvailability::factory()->for($restaurant)->create(['capacity' => 40, 'booked_seats' => 4]);
    $reservation = Reservation::factory()->for($restaurant)->create([
        'service_availability_id' => $slot->id,
        'reservation_date' => CarbonImmutable::today()->toDateString(), // deadline already passed for the organizer
        'service_type' => ServiceType::Dinner,
        'status' => ReservationStatus::Confirmed,
        'party_size' => 4,
    ]);

    $this->postJson("/api/reservations/{$reservation->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', ReservationStatus::Cancelled->value)
        ->assertJsonPath('data.cancelled_by_id', $owner->id);

    $this->assertDatabaseHas('service_availabilities', ['id' => $slot->id, 'booked_seats' => 0]);
});
