<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy as Group;
use Imc\Application\Actions\Auth\LoginAction;

$setupRoutes = function (Slim\App $app): void {
    // Health check
    $app->get('/', function (Request $request, Response $response): Response {
        $response->getBody()->write(json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Auth routes
    $app->group('/auth', function (Group $group): void {
        $group->post('/login', LoginAction::class);
    });

    // Protected API routes — added in Phase 2
    $app->group('/api', function (Group $group): void {
        // Will be populated in Phase 2
    });
};
