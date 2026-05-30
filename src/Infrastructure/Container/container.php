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

// Bootstrap Eloquent Capsule first
$settings = require __DIR__ . '/../../Application/Settings/settings.php';

$capsule = new Capsule();
$capsule->addConnection($settings['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

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
]);

return $containerBuilder->build();
