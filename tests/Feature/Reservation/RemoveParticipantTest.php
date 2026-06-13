<?php

use App\Enums\ParticipantRole;
use App\Models\Reservation;
use App\Models\ReservationParticipant;
use App\Models\User;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

it('lets the organizer remove a guest', function () {
    $organizer = actingAsClient();
    $reservation = Reservation::factory()->for($organizer, 'organizer')->create();
    ReservationParticipant::factory()->for($reservation)->for($organizer)->organizer()->create();
    $guest = ReservationParticipant::factory()->for($reservation)->create();

    $this->deleteJson("/api/reservations/{$reservation->id}/participants/{$guest->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('reservation_participants', ['id' => $guest->id]);
});

it('refuses to remove the organizer participant', function () {
    $organizer = actingAsClient();
    $reservation = Reservation::factory()->for($organizer, 'organizer')->create();
    $organizerParticipant = ReservationParticipant::factory()->for($reservation)->for($organizer)->organizer()->create();

    $this->deleteJson("/api/reservations/{$reservation->id}/participants/{$organizerParticipant->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('reservation_participants', ['id' => $organizerParticipant->id]);
});

it('forbids a non-organizer from removing a participant', function () {
    $organizer = User::factory()->client()->create();
    $reservation = Reservation::factory()->for($organizer, 'organizer')->create();
    $guest = ReservationParticipant::factory()->for($reservation)->create();

    actingAsClient();

    $this->deleteJson("/api/reservations/{$reservation->id}/participants/{$guest->id}")
        ->assertForbidden();
});

it('returns 404 for a participant belonging to another reservation', function () {
    $organizer = actingAsClient();
    $reservation = Reservation::factory()->for($organizer, 'organizer')->create();
    ReservationParticipant::factory()->for($reservation)->for($organizer)->organizer()->create();

    $otherReservation = Reservation::factory()->for($organizer, 'organizer')->create();
    $foreignGuest = ReservationParticipant::factory()->for($otherReservation)->create();

    $this->deleteJson("/api/reservations/{$reservation->id}/participants/{$foreignGuest->id}")
        ->assertNotFound();
});

it('requires authentication to remove a participant', function () {
    $organizer = User::factory()->client()->create();
    $reservation = Reservation::factory()->for($organizer, 'organizer')->create();
    $guest = ReservationParticipant::factory()->for($reservation)->create([
        'role' => ParticipantRole::Guest,
    ]);

    $this->deleteJson("/api/reservations/{$reservation->id}/participants/{$guest->id}")
        ->assertUnauthorized();
});
