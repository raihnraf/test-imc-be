<?php

declare(strict_types=1);

namespace Imc\Domain\Exceptions;

class AuthenticationException extends DomainException
{
    public function __construct(string $message = 'Authentication failed', int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
