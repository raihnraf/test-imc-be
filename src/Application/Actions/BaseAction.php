<?php

declare(strict_types=1);

namespace Imc\Application\Actions;

use Psr\Http\Message\ResponseInterface;

abstract class BaseAction
{
    protected function jsonResponse(ResponseInterface $response, mixed $data, int $status = 200): ResponseInterface
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response->getBody()->write($body !== false ? $body : '');
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    protected function errorResponse(ResponseInterface $response, string $type, string $description, int $status): ResponseInterface
    {
        $payload = [
            'statusCode' => $status,
            'error' => [
                'type' => $type,
                'description' => $description,
            ],
        ];

        return $this->jsonResponse($response, $payload, $status);
    }

    protected function notFoundResponse(ResponseInterface $response, string $message = 'Resource not found'): ResponseInterface
    {
        return $this->errorResponse($response, 'NOT_FOUND', $message, 404);
    }

    protected function validationErrorResponse(ResponseInterface $response, array $errors, string $message = 'Validation failed'): ResponseInterface
    {
        $payload = [
            'statusCode' => 422,
            'error' => [
                'type' => 'VALIDATION_ERROR',
                'description' => $message,
                'errors' => $errors,
            ],
        ];

        return $this->jsonResponse($response, $payload, 422);
    }
}
