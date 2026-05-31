<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Imc\Application\Handlers\HttpErrorHandler;
use Imc\Application\Handlers\JsonErrorRenderer;
use Imc\Application\Middleware\CorsMiddleware;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Build DI container
$container = require __DIR__ . '/../src/Infrastructure/Container/container.php';

// Create Slim App
Slim\Factory\AppFactory::setContainer($container);
$app = Slim\Factory\AppFactory::create();

// Middleware (LIFO: last added = first executed)
// Cors must be outermost to catch OPTIONS preflight before Routing rejects them
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(CorsMiddleware::class);

// Error middleware
$settings = $container->get('settings');
$displayErrorDetails = ($settings['app']['env'] ?? 'production') === 'local';
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);

// Set custom error handler for JSON responses
$errorHandler = new HttpErrorHandler($app->getCallableResolver(), $app->getResponseFactory());
$errorHandler->setErrorRenderer(new JsonErrorRenderer());
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Load routes and middleware
require __DIR__ . '/../routes/routes.php';
require __DIR__ . '/../routes/middleware.php';

// Set up routes closure
$setupRoutes($app);

$app->run();
