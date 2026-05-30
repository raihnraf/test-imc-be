<?php

declare(strict_types=1);

namespace Imc\Domain\Exceptions;

abstract class DomainException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
