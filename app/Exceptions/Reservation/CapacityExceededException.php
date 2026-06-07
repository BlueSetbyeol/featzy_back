<?php

namespace App\Exceptions\Reservation;

use App\Exceptions\DomainException;

final class CapacityExceededException extends DomainException
{
    public function __construct(string $message = 'The requested party size exceeds the remaining capacity for this service.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'CAPACITY_EXCEEDED';
    }
}
