<?php

namespace App\Events\Reservation;

use App\Models\ReservationParticipant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationResponded
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReservationParticipant $participant) {}
}
