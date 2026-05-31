<?php

declare(strict_types=1);

namespace Imc\Application\Middleware;

use Imc\Domain\Exceptions\AuthenticationException;
use Imc\Domain\Token\TokenService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class JwtMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TokenService $tokenService,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->errorResponse(401, 'TOKEN_MISSING', 'Authorization token is missing');
        }

        $token = substr($authHeader, 7);

        try {
            $data = $this->tokenService->validateToken($token);

            $request = $request
                ->withAttribute('user_id', $data['user_id'])
                ->withAttribute('level_id', $data['level_id'])
                ->withAttribute('username', $data['username']);

            return $handler->handle($request);
        } catch (AuthenticationException $e) {
            return $this->errorResponse($e->getCode(), 'INVALID_TOKEN', $e->getMessage());
        }
    }

    private function errorResponse(int $status, string $type, string $description): Response
    {
        $response = $this->responseFactory->createResponse($status);

        $body = json_encode([
            'statusCode' => $status,
            'error' => [
                'type' => $type,
                'description' => $description,
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response->getBody()->write($body !== false ? $body : '');
        return $response->withHeader('Content-Type', 'application/json');
    }
}
