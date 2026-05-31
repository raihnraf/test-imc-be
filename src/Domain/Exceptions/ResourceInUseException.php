<?php

declare(strict_types=1);

namespace Imc\Domain\Exceptions;

class ResourceInUseException extends DomainException
{
    private string $field;

    public function __construct(string $message = 'Resource is still in use', string $field = '', int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
