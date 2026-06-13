<?php

namespace App\Events\Reservation;

use App\Models\Reservation;
use App\Models\ReservationParticipant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantInvited
{
    use Dispatchable, SerializesModels;

    /**
     * @param  Collection<int, ReservationParticipant>  $participants
     */
    public function __construct(
        public Reservation $reservation,
        public Collection $participants,
    ) {}
}
