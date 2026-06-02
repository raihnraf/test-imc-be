<?php

declare(strict_types=1);

namespace Imc\Application\Middleware;

use Imc\Domain\RateLimit\RateLimitRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimitRepositoryInterface $rateLimitRepo,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly int $maxAttempts = 5,
        private readonly int $windowSeconds = 60,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $serverParams = $request->getServerParams();
        $clientIp = $serverParams['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($this->rateLimitRepo->isRateLimited($clientIp, $this->maxAttempts, $this->windowSeconds)) {
            $retryAfter = $this->rateLimitRepo->getSecondsUntilReset($clientIp, $this->windowSeconds);
            
            $response = $this->responseFactory->createResponse(429);
            $body = json_encode([
                'statusCode' => 429,
                'error' => [
                    'type' => 'RATE_LIMITED',
                    'description' => 'Too many login attempts. Please try again later.',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $response->getBody()->write($body !== false ? $body : '');
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) $retryAfter);
        }

        $response = $handler->handle($request);

        return $response;
    }
}
