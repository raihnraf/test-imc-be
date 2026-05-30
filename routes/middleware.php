<?php

declare(strict_types=1);

/**
 * Middleware Registration
 *
 * LIFO execution order (last added = first executed):
 * 1. ErrorMiddleware (from index.php — runs first on errors)
 * 2. JwtMiddleware — validate token for /api/* routes
 * 3. RoutingMiddleware (from index.php)
 * 4. BodyParsingMiddleware (from index.php)
 *
 * Register middleware here:
 *   $app->add(JwtMiddleware::class);
 *   $app->add(PermissionMiddleware::class);
 */
