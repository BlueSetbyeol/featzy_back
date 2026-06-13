<?php

use App\Enums\InvitationStatus;
use App\Enums\ParticipantRole;
use App\Events\Reservation\ParticipantInvited;
use App\Models\FriendGroup;
use App\Models\Reservation;
use App\Models\ReservationParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->withHeader('Origin', config('app.frontend_url'));
});

/**
 * A confirmed reservation hosted by $organizer, with the organizer already
 * persisted as the first participant (so the party-size cap counts correctly).
 */
function hostReservation(User $organizer, int $partySize = 4): Reservation
{
    $reservation = Reservation::factory()->for($organizer, 'organizer')->create([
        'party_size' => $partySize,
    ]);

    ReservationParticipant::factory()->for($reservation)->for($organizer)->organizer()->create();

    return $reservation;
}

it('invites guests by user id', function () {
    Event::fake([ParticipantInvited::class]);

    $organizer = actingAsClient();
    $reservation = hostReservation($organizer);
    $guests = User::factory()->client()->count(2)->create();

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'user_ids' => $guests->pluck('id')->all(),
    ])
        ->assertCreated()
        ->assertJsonCount(2, 'data')
        // Co-participants see identity only — never the email/phone in UserResource.
        ->assertJsonStructure(['data' => [['user' => ['id', 'first_name', 'last_name', 'name']]]])
        ->assertJsonMissingPath('data.0.user.email')
        ->assertJsonMissingPath('data.0.user.phone');

    foreach ($guests as $guest) {
        $this->assertDatabaseHas('reservation_participants', [
            'reservation_id' => $reservation->id,
            'user_id' => $guest->id,
            'role' => ParticipantRole::Guest->value,
            'invitation_status' => InvitationStatus::Pending->value,
        ]);
    }

    Event::assertDispatched(ParticipantInvited::class);
});

it('invites all members of an owned friend group', function () {
    $organizer = actingAsClient();
    $reservation = hostReservation($organizer);
    $members = User::factory()->client()->count(2)->create();
    $group = FriendGroup::factory()->for($organizer, 'owner')->create();
    $group->members()->attach($members->pluck('id'));

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'friend_group_id' => $group->id,
    ])
        ->assertCreated()
        ->assertJsonCount(2, 'data');
});

it('merges and de-duplicates user ids and friend group members', function () {
    $organizer = actingAsClient();
    $reservation = hostReservation($organizer);
    $shared = User::factory()->client()->create();
    $other = User::factory()->client()->create();
    $group = FriendGroup::factory()->for($organizer, 'owner')->create();
    $group->members()->attach([$shared->id, $other->id]);

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'user_ids' => [$shared->id],
        'friend_group_id' => $group->id,
    ])
        ->assertCreated()
        ->assertJsonCount(2, 'data');
});

it('rejects re-inviting an existing participant', function () {
    $organizer = actingAsClient();
    $reservation = hostReservation($organizer);
    $guest = User::factory()->client()->create();
    ReservationParticipant::factory()->for($reservation)->for($guest)->create();

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'user_ids' => [$guest->id],
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'ALREADY_INVITED');
});

it('rejects inviting oneself', function () {
    $organizer = actingAsClient();
    $reservation = hostReservation($organizer);

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'user_ids' => [$organizer->id],
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'ALREADY_INVITED');
});

it('allows inviting up to exactly the party size', function () {
    $organizer = actingAsClient();
    $reservation = hostReservation($organizer, partySize: 3);
    $guests = User::factory()->client()->count(2)->create(); // organizer + 2 = 3

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'user_ids' => $guests->pluck('id')->all(),
    ])
        ->assertCreated()
        ->assertJsonCount(2, 'data');

    $this->assertDatabaseCount('reservation_participants', 3);
});

it('rejects inviting beyond the party size', function () {
    $organizer = actingAsClient();
    $reservation = hostReservation($organizer, partySize: 2);
    $guests = User::factory()->client()->count(2)->create();

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'user_ids' => $guests->pluck('id')->all(),
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'PARTICIPANT_LIMIT_EXCEEDED');

    $this->assertDatabaseCount('reservation_participants', 1); // only the organizer
});

it('forbids a non-organizer from inviting', function () {
    $organizer = User::factory()->client()->create();
    $reservation = hostReservation($organizer);

    actingAsClient();
    $guest = User::factory()->client()->create();

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'user_ids' => [$guest->id],
    ])->assertForbidden();
});

it('rejects a friend group owned by someone else', function () {
    $organizer = actingAsClient();
    $reservation = hostReservation($organizer);
    $group = FriendGroup::factory()->create(); // owned by another user

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'friend_group_id' => $group->id,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('friend_group_id');
});

it('requires user ids or a friend group', function () {
    $organizer = actingAsClient();
    $reservation = hostReservation($organizer);

    $this->postJson("/api/reservations/{$reservation->id}/participants", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_ids', 'friend_group_id']);
});

it('requires authentication to invite', function () {
    $organizer = User::factory()->client()->create();
    $reservation = hostReservation($organizer);

    $this->postJson("/api/reservations/{$reservation->id}/participants", [
        'user_ids' => [User::factory()->client()->create()->id],
    ])->assertUnauthorized();
});
