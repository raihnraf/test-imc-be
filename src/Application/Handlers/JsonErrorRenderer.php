<?php

declare(strict_types=1);

namespace Imc\Application\Handlers;

use Imc\Domain\Exceptions\AuthenticationException;
use Imc\Domain\Exceptions\AuthorizationException;
use Imc\Domain\Exceptions\ValidationException;
use Imc\Domain\Exceptions\NotFoundException;
use Imc\Domain\Exceptions\DuplicateEntryException;
use Imc\Domain\Exceptions\DomainException;
use Slim\Exception\HttpSpecializedException;
use Slim\Interfaces\ErrorRendererInterface;

class JsonErrorRenderer implements ErrorRendererInterface
{
    public function __invoke(\Throwable $exception, bool $displayErrorDetails): string
    {
        if ($exception instanceof DomainException) {
            $status = $exception->getCode();
            $type = $this->mapExceptionType($exception);
        } elseif ($exception instanceof HttpSpecializedException) {
            $status = $exception->getCode();
            $type = $exception->getCode() === 404 ? 'NOT_FOUND' : 'HTTP_ERROR';
        } else {
            $status = 500;
            $type = 'INTERNAL_SERVER_ERROR';
        }

        $description = $displayErrorDetails
            ? $exception->getMessage()
            : 'An internal error occurred';

        $error = [
            'statusCode' => $status,
            'error' => [
                'type' => $type,
                'description' => $description,
            ],
        ];

        if ($exception instanceof ValidationException) {
            $error['error']['errors'] = $exception->getErrors();
        }

        if ($exception instanceof DuplicateEntryException) {
            $error['error']['field'] = $exception->getField();
        }

        if ($displayErrorDetails) {
            $error['error']['trace'] = $exception->getTraceAsString();
        }

        return json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function mapExceptionType(\Throwable $exception): string
    {
        return match (true) {
            $exception instanceof AuthenticationException => 'UNAUTHENTICATED',
            $exception instanceof AuthorizationException => 'FORBIDDEN',
            $exception instanceof ValidationException => 'VALIDATION_ERROR',
            $exception instanceof NotFoundException => 'NOT_FOUND',
            $exception instanceof DuplicateEntryException => 'DUPLICATE_ENTRY',
            default => 'INTERNAL_SERVER_ERROR',
        };
    }
}
