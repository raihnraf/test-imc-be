<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Imc\Application\Actions\Auth\LoginAction;
use Imc\Application\Actions\Auth\RefreshTokenAction;
use Imc\Application\Middleware\JwtMiddleware;
use Imc\Application\Validation\LevelValidator;
use Imc\Application\Validation\PageValidator;
use Imc\Application\Validation\UserValidator;
use Imc\Domain\Level\LevelRepository;
use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Domain\Page\PageRepository;
use Imc\Domain\Page\PageRepositoryInterface;
use Imc\Domain\Permission\PermissionRepository;
use Imc\Domain\Permission\PermissionRepositoryInterface;
use Imc\Domain\RateLimit\RateLimitRepository;
use Imc\Domain\RateLimit\RateLimitRepositoryInterface;
use Imc\Domain\RefreshToken\RefreshTokenRepository;
use Imc\Domain\RefreshToken\RefreshTokenRepositoryInterface;
use Imc\Domain\Token\TokenService;
use Imc\Domain\User\UserRepository;
use Imc\Domain\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

// Bootstrap Eloquent Capsule first
$settings = require __DIR__ . '/../../Application/Settings/settings.php';

// Always reinitialize Capsule — PHP built-in server keeps static state
// between requests which can leave stale/null connections
$capsule = null;
try {
    $capsule = Capsule::instance();
    // Verify the instance actually has a valid connection
    if ($capsule !== null) {
        $capsule->getConnection()->getPdo();
    }
} catch (\Throwable $e) {
    // Instance is stale or doesn't exist — create fresh
    $capsule = null;
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

    JwtMiddleware::class => \DI\autowire(),

    LevelValidator::class => \DI\autowire(),
    UserValidator::class => \DI\autowire(),
    PageValidator::class => \DI\autowire(),
]);

return $containerBuilder->build();
