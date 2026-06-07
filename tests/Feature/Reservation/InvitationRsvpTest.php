<?php

use App\Enums\InvitationStatus;
use App\Enums\ParticipantRole;
use App\Events\Reservation\InvitationResponded;
use App\Models\Reservation;
use App\Models\ReservationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

/**
 * Make $user a pending guest of a fresh reservation and return that reservation.
 */
function invitedReservation(User $user): Reservation
{
    $reservation = Reservation::factory()->create();
    ReservationParticipant::factory()->for($reservation)->for($user)->create([
        'role' => ParticipantRole::Guest,
        'invitation_status' => InvitationStatus::Pending,
    ]);

    return $reservation;
}

it('lists the guest invitations of the user', function () {
    $user = actingAsClient();
    invitedReservation($user);
    invitedReservation($user);
    ReservationParticipant::factory()->count(3)->create(['role' => ParticipantRole::Guest]); // others

    $this->getJson('/api/me/invitations')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('accepts an invitation and marks attendance', function () {
    Event::fake([InvitationResponded::class]);

    $user = actingAsClient();
    $reservation = invitedReservation($user);

    $this->postJson("/api/reservations/{$reservation->id}/rsvp", [
        'status' => InvitationStatus::Accepted->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.invitation_status', InvitationStatus::Accepted->value)
        ->assertJsonPath('data.is_attending', true);

    $this->assertDatabaseHas('reservation_participants', [
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'invitation_status' => InvitationStatus::Accepted->value,
        'is_attending' => true,
    ]);

    Event::assertDispatched(InvitationResponded::class);
});

it('declines an invitation and clears attendance', function () {
    $user = actingAsClient();
    $reservation = invitedReservation($user);

    $this->postJson("/api/reservations/{$reservation->id}/rsvp", [
        'status' => InvitationStatus::Declined->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.invitation_status', InvitationStatus::Declined->value)
        ->assertJsonPath('data.is_attending', false);
});

it('validates the rsvp status', function () {
    $user = actingAsClient();
    $reservation = invitedReservation($user);

    $this->postJson("/api/reservations/{$reservation->id}/rsvp", [
        'status' => 'pending',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('forbids rsvp from a user who is not a guest', function () {
    actingAsClient();
    $reservation = Reservation::factory()->create();

    $this->postJson("/api/reservations/{$reservation->id}/rsvp", [
        'status' => InvitationStatus::Accepted->value,
    ])->assertForbidden();
});

it('forbids the organizer from responding to an rsvp', function () {
    $user = actingAsClient();
    $reservation = Reservation::factory()->for($user, 'organizer')->create();
    ReservationParticipant::factory()->for($reservation)->for($user)->organizer()->create();

    $this->postJson("/api/reservations/{$reservation->id}/rsvp", [
        'status' => InvitationStatus::Accepted->value,
    ])->assertForbidden();
});

it('requires authentication to rsvp', function () {
    $reservation = Reservation::factory()->create();

    $this->postJson("/api/reservations/{$reservation->id}/rsvp", [
        'status' => InvitationStatus::Accepted->value,
    ])->assertUnauthorized();
});

it('requires authentication to list invitations', function () {
    $this->getJson('/api/me/invitations')->assertUnauthorized();
});
