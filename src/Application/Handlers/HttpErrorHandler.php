<?php

declare(strict_types=1);

namespace Imc\Application\Handlers;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpSpecializedException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;

class HttpErrorHandler extends SlimErrorHandler
{
    protected function respond(): ResponseInterface
    {
        $statusCode = 500;

        if ($this->exception instanceof HttpSpecializedException) {
            $statusCode = $this->exception->getCode();
        } elseif ($this->exception->getCode() >= 400 && $this->exception->getCode() < 600) {
            $statusCode = $this->exception->getCode();
        }

        $response = $this->responseFactory->createResponse($statusCode);

        $renderer = new JsonErrorRenderer();
        $body = $renderer($this->exception, $this->displayErrorDetails);

        $response->getBody()->write($body !== false ? $body : '');
        return $response->withHeader('Content-Type', 'application/json');
    }

    protected function logError(string $error): void
    {
        $timestamp = '[' . date('Y-m-d H:i:s') . '] ';
        error_log($timestamp . $error);
    }
}
