<?php

declare(strict_types=1);

namespace Imc\Application\Middleware;

use Imc\Domain\Page\PageRepositoryInterface;
use Imc\Domain\Permission\PermissionRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepo,
        private readonly PageRepositoryInterface $pageRepo,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $levelId = (int) $request->getAttribute('level_id');

        $apiPath = preg_replace('#^/api#', '', $request->getUri()->getPath());

        $segments = explode('/', trim($apiPath, '/'));
        $basePath = '/' . ($segments[0] ?? '');

        $page = $this->pageRepo->findByRoute($basePath);

        if ($page === null || !$page->isActive) {
            return $handler->handle($request);
        }

        if (!$this->permissionRepo->hasAccess($userId, $levelId, $basePath)) {
            $response = $this->responseFactory->createResponse(403);
            $body = json_encode([
                'statusCode' => 403,
                'error' => [
                    'type' => 'FORBIDDEN',
                    'description' => 'You do not have permission to access this resource',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $response->getBody()->write($body !== false ? $body : '');
            return $response->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
