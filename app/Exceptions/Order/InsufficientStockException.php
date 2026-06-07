<?php

namespace App\Exceptions\Order;

use App\Exceptions\DomainException;

final class InsufficientStockException extends DomainException
{
    public function __construct(string $message = 'One or more items do not have enough stock for the requested quantity.')
    {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'INSUFFICIENT_STOCK';
    }
}
