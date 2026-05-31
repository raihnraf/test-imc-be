<?php

declare(strict_types=1);

namespace Imc\Domain\Exceptions;

class AuthenticationException extends DomainException
{
    private string $errorType;

    public function __construct(
        string $message = 'Authentication failed',
        string $errorType = 'UNAUTHENTICATED',
        int $code = 401,
        ?\Throwable $previous = null
    ) {
        $this->errorType = $errorType;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }
}
