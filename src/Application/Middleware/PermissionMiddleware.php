<?php

declare(strict_types=1);

namespace Imc\Application\Middleware;

use Imc\Domain\Permission\PermissionRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

class PermissionMiddleware implements MiddlewareInterface
{
    private array $pageMap = [
        '/levels' => '/levels',
        '/users' => '/users',
        '/pages' => '/dashboard',
        '/permissions/matrix' => '/dashboard',
    ];

    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepo,
        private readonly ResponseFactory $responseFactory,
    ) {}

    public function process(Request $request, RequestHandler $handler): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $levelId = (int) $request->getAttribute('level_id');

        $uriPath = $request->getUri()->getPath();

        // Strip /api prefix and map to page
        $apiPath = preg_replace('#^/api#', '', $uriPath);

        // Extract base path — match against /levels, /users, /pages, /permissions/matrix
        $matchedPage = null;
        foreach ($this->pageMap as $prefix => $page) {
            if (str_starts_with($apiPath, $prefix)) {
                $matchedPage = $page;
                break;
            }
        }

        if ($matchedPage === null) {
            return $handler->handle($request);
        }

        if (!$this->permissionRepo->hasAccess($userId, $levelId, $matchedPage)) {
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
