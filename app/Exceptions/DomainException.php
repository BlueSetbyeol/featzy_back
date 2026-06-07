<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base class for business/domain failures. Each concrete exception maps to a
 * specific HTTP status and a stable, machine-readable error code. They are
 * rendered centrally as JSON ({message, code}) in bootstrap/app.php, so Actions
 * may throw them freely without crafting HTTP responses themselves.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * The HTTP status code this domain failure should be rendered with.
     */
    abstract public function statusCode(): int;

    /**
     * A stable, machine-readable error code (SCREAMING_SNAKE_CASE) for the SPA.
     */
    abstract public function errorCode(): string;
}
