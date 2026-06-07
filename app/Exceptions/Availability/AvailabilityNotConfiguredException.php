<?php

namespace App\Exceptions\Availability;

use App\Exceptions\DomainException;

final class AvailabilityNotConfiguredException extends DomainException
{
    public function __construct(string $message = 'No service availability has been configured for the requested date.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'AVAILABILITY_NOT_CONFIGURED';
    }
}
