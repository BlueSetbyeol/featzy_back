<?php

namespace App\Actions\Reservation;

use App\Enums\InvitationStatus;
use App\Events\Reservation\InvitationResponded;
use App\Models\Reservation;
use App\Models\ReservationParticipant;
use App\Models\User;

class RespondToInvitationAction
{
    /**
     * Record a guest's response to their invitation. Attendance mirrors the
     * response: accepting marks them attending, declining clears it.
     */
    public function handle(Reservation $reservation, User $guest, InvitationStatus $status): ReservationParticipant
    {
        $participant = $reservation->participants()
            ->where('user_id', $guest->id)
            ->firstOrFail();

        $participant->update([
            'invitation_status' => $status->value,
            'is_attending' => $status === InvitationStatus::Accepted,
            'responded_at' => now(),
        ]);

        InvitationResponded::dispatch($participant);

        return $participant->load('user');
    }
}
