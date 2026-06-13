<?php

namespace App\Policies;

use App\Enums\ParticipantRole;
use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /**
     * The organizer, any participant, or the restaurant owner may view a
     * reservation. Relationship queries are used instead of loaded relations so
     * the check never triggers lazy loading.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        if ($reservation->organizer_id === $user->id) {
            return true;
        }

        if ($reservation->restaurant()->where('owner_id', $user->id)->exists()) {
            return true;
        }

        return $reservation->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * The organizer or the restaurant owner may cancel; the owner bypasses the
     * cancellation deadline (enforced in CancelReservationAction).
     */
    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->organizer_id === $user->id
            || $reservation->restaurant()->where('owner_id', $user->id)->exists();
    }

    public function invite(User $user, Reservation $reservation): bool
    {
        return $reservation->organizer_id === $user->id;
    }

    /**
     * Restaurant-side lifecycle actions (seat / complete / no-show) are owner-only.
     */
    public function manage(User $user, Reservation $reservation): bool
    {
        return $reservation->restaurant()->where('owner_id', $user->id)->exists();
    }

    /**
     * Only a guest of the reservation may respond to their own invitation; the
     * organizer is auto-accepted and has nothing to RSVP to.
     */
    public function rsvp(User $user, Reservation $reservation): bool
    {
        return $reservation->participants()
            ->where('user_id', $user->id)
            ->where('role', ParticipantRole::Guest->value)
            ->exists();
    }

    /**
     * Any participant may open the (single) pre-order for the reservation.
     */
    public function createOrder(User $user, Reservation $reservation): bool
    {
        return $reservation->participants()
            ->where('user_id', $user->id)
            ->exists();
    }
}
