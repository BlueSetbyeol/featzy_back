<?php

namespace App\Data\Reservation;

use Spatie\LaravelData\Data;

class InviteParticipantsData extends Data
{
    /**
     * @param  array<int, int>|null  $user_ids
     */
    public function __construct(
        public ?array $user_ids = null,
        public ?int $friend_group_id = null,
    ) {}
}
