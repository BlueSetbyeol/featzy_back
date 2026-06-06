<?php

namespace App\Observers;

use App\Models\Reservation;
use Illuminate\Support\Str;

class ReservationObserver
{
    /**
     * Handle the Reservation "creating" event.
     */
    public function creating(Reservation $reservation): void
    {
        if (empty($reservation->public_uuid)) {
            $reservation->public_uuid = (string) Str::uuid();
        }
    }
}
