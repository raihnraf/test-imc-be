<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Imc\Domain\Token\TokenService;
use Imc\Domain\User\UserRepositoryInterface;
use Imc\Domain\User\UserRepository;
use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Domain\Level\LevelRepository;
use Imc\Domain\Page\PageRepositoryInterface;
use Imc\Domain\Page\PageRepository;
use Imc\Domain\Permission\PermissionRepositoryInterface;
use Imc\Domain\Permission\PermissionRepository;
use Imc\Domain\RateLimit\RateLimitRepositoryInterface;
use Imc\Domain\RateLimit\RateLimitRepository;
use Imc\Domain\RefreshToken\RefreshTokenRepositoryInterface;
use Imc\Domain\RefreshToken\RefreshTokenRepository;
use Imc\Application\Actions\Auth\LoginAction;
use Imc\Application\Actions\Auth\RefreshTokenAction;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Imc\Application\Middleware\RateLimitMiddleware;

// Bootstrap Eloquent Capsule first
$settings = require __DIR__ . '/../../Application/Settings/settings.php';

// Skip if Capsule already initialized (e.g., from test suite)
$capsule = null;
try {
    $capsule = Capsule::instance();
} catch (\BadMethodCallException $e) {
    // No global instance set yet — proceed to create one
}

if ($capsule === null) {
    $capsule = new Capsule();
    $capsule->addConnection($settings['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);

$containerBuilder->addDefinitions([
    'settings' => $settings,

    Capsule::class => $capsule,

    TokenService::class => function () use ($settings) {
        return new TokenService($settings['jwt']);
    },

    UserRepositoryInterface::class => \DI\autowire(UserRepository::class),
    LevelRepositoryInterface::class => \DI\autowire(LevelRepository::class),
    PageRepositoryInterface::class => \DI\autowire(PageRepository::class),
    PermissionRepositoryInterface::class => \DI\autowire(PermissionRepository::class),
    RateLimitRepositoryInterface::class => \DI\autowire(RateLimitRepository::class),
    RefreshTokenRepositoryInterface::class => \DI\autowire(RefreshTokenRepository::class),

    LoginAction::class => \DI\autowire()->constructorParameter('settings', \DI\get('settings')),
    RefreshTokenAction::class => \DI\autowire()->constructorParameter('settings', \DI\get('settings')),

    ResponseFactoryInterface::class => \DI\autowire(ResponseFactory::class),
]);

return $containerBuilder->build();
