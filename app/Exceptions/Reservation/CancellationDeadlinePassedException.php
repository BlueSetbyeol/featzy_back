<?php

namespace App\Exceptions\Reservation;

use App\Exceptions\DomainException;

final class CancellationDeadlinePassedException extends DomainException
{
    public function __construct(string $message = 'The cancellation deadline for this reservation has passed.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'CANCELLATION_DEADLINE_PASSED';
    }
}
