<?php

namespace App\Exceptions\Reservation;

use App\Exceptions\DomainException;

final class ParticipantLimitExceededException extends DomainException
{
    public function __construct(string $message = 'Inviting these guests would exceed the reservation party size.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PARTICIPANT_LIMIT_EXCEEDED';
    }
}
