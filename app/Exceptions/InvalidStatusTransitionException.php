<?php

namespace App\Exceptions;

final class InvalidStatusTransitionException extends DomainException
{
    public function __construct(string $message = 'The requested status transition is not allowed.')
    {
        parent::__construct($message);
    }

    /**
     * Build the exception from the offending source and target states.
     */
    public static function between(string $from, string $to): self
    {
        return new self("Cannot transition from \"{$from}\" to \"{$to}\".");
    }

    public function statusCode(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'INVALID_STATUS_TRANSITION';
    }
}
