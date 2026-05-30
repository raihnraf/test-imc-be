<?php

declare(strict_types=1);

namespace Imc\Domain\Exceptions;

class AuthorizationException extends DomainException
{
    public function __construct(string $message = 'Access denied', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
