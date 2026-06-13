<?php

namespace App\Policies;

use App\Enums\ParticipantRole;
use App\Models\ReservationParticipant;
use App\Models\User;

class ReservationParticipantPolicy
{
    /**
     * Only the organizer of the participant's reservation may remove a guest, and
     * the organizer participant itself can never be removed.
     */
    public function delete(User $user, ReservationParticipant $participant): bool
    {
        if ($participant->role === ParticipantRole::Organizer) {
            return false;
        }

        return $participant->reservation()->where('organizer_id', $user->id)->exists();
    }
}
