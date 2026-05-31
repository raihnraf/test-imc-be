<?php

declare(strict_types=1);

namespace Imc\Application\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait DispatchByMethod
{
    /**
     * @param array<string, string> $args
     */
    private function dispatch(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $method = $request->getMethod();
        $id = isset($args['id']) ? (int) $args['id'] : null;

        return match ($method) {
            'GET' => $id !== null
                ? $this->get($request, $response, $id)
                : $this->list($request, $response),
            'POST' => $this->create($request, $response),
            'PUT' => $this->update($request, $response, $id),
            'DELETE' => $this->delete($request, $response, $id),
            default => $this->errorResponse($response, 'METHOD_NOT_ALLOWED', 'Method not allowed', 405),
        };
    }

    abstract protected function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
    abstract protected function get(ServerRequestInterface $request, ResponseInterface $response, int $id): ResponseInterface;
    abstract protected function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
    abstract protected function update(ServerRequestInterface $request, ResponseInterface $response, ?int $id): ResponseInterface;
    abstract protected function delete(ServerRequestInterface $request, ResponseInterface $response, ?int $id): ResponseInterface;
    abstract protected function errorResponse(ResponseInterface $response, string $type, string $description, int $status): ResponseInterface;
}
