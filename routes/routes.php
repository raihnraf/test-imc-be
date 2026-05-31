<?php

declare(strict_types=1);

use Imc\Application\Actions\Auth\LoginAction;
use Imc\Application\Actions\Auth\RefreshTokenAction;
use Imc\Application\Actions\Level\LevelAction;
use Imc\Application\Actions\Page\PageAction;
use Imc\Application\Actions\Permission\LevelPermissionAction;
use Imc\Application\Actions\Permission\PermissionMatrixAction;
use Imc\Application\Actions\Permission\UserPermissionAction;
use Imc\Application\Actions\User\UserAction;
use Imc\Application\Middleware\JwtMiddleware;
use Imc\Application\Middleware\PermissionMiddleware;
use Imc\Application\Middleware\RateLimitMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy as Group;

$setupRoutes = function (Slim\App $app): void {
    // Health check
    $app->get('/', function (Request $request, Response $response): Response {
        $response->getBody()->write(json_encode(['status' => 'ok'], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Auth routes
    $app->group('/auth', function (Group $group): void {
        $group->post('/login', LoginAction::class)->add(RateLimitMiddleware::class);
        $group->post('/refresh', RefreshTokenAction::class);
    });

    // Protected API routes
    $app->group('/api', function (Group $group): void {
        // Levels
        $group->group('/levels', function (Group $group): void {
            $group->get('', LevelAction::class);
            $group->get('/{id}', LevelAction::class);
            $group->post('', LevelAction::class);
            $group->put('/{id}', LevelAction::class);
            $group->delete('/{id}', LevelAction::class);
        });

        // Level Permissions
        $group->group('/levels/{levelId}/permissions', function (Group $group): void {
            $group->get('', LevelPermissionAction::class);
            $group->post('', LevelPermissionAction::class);
            $group->delete('', LevelPermissionAction::class);
        });

        // Users
        $group->group('/users', function (Group $group): void {
            $group->get('', UserAction::class);
            $group->get('/{id}', UserAction::class);
            $group->post('', UserAction::class);
            $group->put('/{id}', UserAction::class);
            $group->delete('/{id}', UserAction::class);
        });

        // User Permissions
        $group->group('/users/{userId}/permissions', function (Group $group): void {
            $group->get('', UserPermissionAction::class);
            $group->post('', UserPermissionAction::class);
            $group->delete('', UserPermissionAction::class);
        });

        // Permission Matrix
        $group->get('/permissions/matrix', PermissionMatrixAction::class);

        // Pages
        $group->group('/pages', function (Group $group): void {
            $group->get('', PageAction::class);
            $group->get('/{id}', PageAction::class);
            $group->post('', PageAction::class);
            $group->put('/{id}', PageAction::class);
            $group->delete('/{id}', PageAction::class);
        });
    })->add(PermissionMiddleware::class)->add(JwtMiddleware::class);
};
