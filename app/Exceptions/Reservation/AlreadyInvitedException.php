<?php

namespace App\Exceptions\Reservation;

use App\Exceptions\DomainException;

final class AlreadyInvitedException extends DomainException
{
    public function __construct(string $message = 'One or more of the selected users are already part of this reservation.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'ALREADY_INVITED';
    }
}
