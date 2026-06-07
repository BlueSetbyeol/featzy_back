<?php

namespace App\Exceptions\Order;

use App\Exceptions\DomainException;

final class PreordersNotAcceptedException extends DomainException
{
    public function __construct(string $message = 'This restaurant does not accept pre-orders.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PREORDERS_NOT_ACCEPTED';
    }
}
