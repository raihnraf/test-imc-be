<?php

declare(strict_types=1);

namespace Imc\Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;

abstract class TestCase extends PHPUnitTestCase
{
    protected ?App $app = null;
    private static ?Capsule $sharedCapsule = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Ensure consistent JWT config regardless of how phpunit is invoked
        // (e.g. --no-configuration skips phpunit.xml <env> definitions)
        $_ENV['JWT_SECRET'] = $_ENV['JWT_SECRET'] ?: 'test-secret-key-for-phpunit';
        $_ENV['JWT_ALGORITHM'] = $_ENV['JWT_ALGORITHM'] ?: 'HS256';
        $_ENV['JWT_EXPIRY'] = $_ENV['JWT_EXPIRY'] ?: '3600';
        $_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?: 'testing';

        if (self::$sharedCapsule === null) {
            $dbSettings = [
                'driver' => 'pgsql',
                'host' => getenv('DB_HOST') ?: 'localhost',
                'port' => getenv('DB_PORT') ?: '5432',
                'database' => getenv('DB_DATABASE') ?: 'imc',
                'username' => getenv('DB_USERNAME') ?: 'postgres',
                'password' => getenv('DB_PASSWORD') ?: 'postgres',
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
            ];

            self::$sharedCapsule = new Capsule();
            self::$sharedCapsule->addConnection($dbSettings);
            self::$sharedCapsule->setAsGlobal();
            self::$sharedCapsule->bootEloquent();

            // Seed once for entire test class
            if (Capsule::table('levels')->count() === 0) {
                Capsule::table('levels')->insert([
                    ['nama_level' => 'Super Admin', 'deskripsi' => 'Full system access', 'is_active' => true, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                    ['nama_level' => 'Manager', 'deskripsi' => 'Content management', 'is_active' => true, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                    ['nama_level' => 'Staff', 'deskripsi' => 'Operational access', 'is_active' => true, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                    ['nama_level' => 'Viewer', 'deskripsi' => 'Read-only access', 'is_active' => true, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                ]);
            }

            if (Capsule::table('pages')->count() === 0) {
                Capsule::table('pages')->insert([
                    ['nama_page' => 'Dashboard', 'route_path' => '/dashboard', 'deskripsi' => 'Main dashboard', 'urutan_tampil' => 1, 'is_active' => true, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                    ['nama_page' => 'User Management', 'route_path' => '/users', 'deskripsi' => 'Manage users', 'urutan_tampil' => 2, 'is_active' => true, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                    ['nama_page' => 'Level Management', 'route_path' => '/levels', 'deskripsi' => 'Manage levels', 'urutan_tampil' => 3, 'is_active' => true, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                ]);
            }

            if (Capsule::table('users')->where('username', 'admin')->count() === 0) {
                Capsule::table('users')->insert([
                    'nama_lengkap' => 'Super Admin',
                    'username' => 'admin',
                    'email' => 'admin@imc.local',
                    'password' => password_hash('admin123', PASSWORD_ARGON2ID),
                    'level_id' => 1,
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Grant level 1 (Super Admin) access to all pages so admin can call all endpoints
            if (Capsule::table('level_permissions')->where('level_id', 1)->count() === 0) {
                Capsule::table('level_permissions')->insert([
                    ['level_id' => 1, 'page_id' => 1], // Dashboard
                    ['level_id' => 1, 'page_id' => 2], // Users
                    ['level_id' => 1, 'page_id' => 3], // Levels
                ]);
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createApplication();
        $this->ensureBaselinePermissions();
        Capsule::table('login_attempts')->truncate();
        Capsule::table('refresh_tokens')->truncate();
    }

    protected function ensureBaselinePermissions(): void
    {
        // Ensure level 1 (Super Admin) has access to all pages
        $existing = Capsule::table('level_permissions')
            ->where('level_id', 1)
            ->pluck('page_id')
            ->toArray();

        $allPages = Capsule::table('pages')->where('is_active', true)->pluck('id')->toArray();
        $missing = array_diff($allPages, $existing);

        foreach ($missing as $pageId) {
            Capsule::table('level_permissions')->insert([
                'level_id' => 1,
                'page_id' => (int) $pageId,
            ]);
        }
    }

    protected function tearDown(): void
    {
        $this->app = null;
        parent::tearDown();
    }

    protected function createApplication(): void
    {
        $container = require __DIR__ . '/../src/Infrastructure/Container/container.php';

        \Slim\Factory\AppFactory::setContainer($container);
        $this->app = \Slim\Factory\AppFactory::create();

        $this->app->addBodyParsingMiddleware();
        $this->app->addRoutingMiddleware();

        $errorMiddleware = $this->app->addErrorMiddleware(true, true, true);

        $errorHandler = new \Imc\Application\Handlers\HttpErrorHandler(
            $this->app->getCallableResolver(),
            $this->app->getResponseFactory()
        );
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        require __DIR__ . '/../routes/routes.php';
        require __DIR__ . '/../routes/middleware.php';
        $setupRoutes($this->app);
    }

    protected function createRequest(string $method, string $path, ?array $body = null, ?string $token = null): ServerRequestInterface
    {
        $uri = (new UriFactory())->createUri($path);
        $headers = ['Content-Type' => 'application/json'];

        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $serverRequest = (new ServerRequestFactory())->createServerRequest($method, $uri);

        if ($body !== null) {
            $stream = (new StreamFactory())->createStream(json_encode($body));
            $serverRequest = $serverRequest->withBody($stream);
        }

        foreach ($headers as $name => $value) {
            $serverRequest = $serverRequest->withHeader($name, $value);
        }

        return $serverRequest;
    }

    protected function handle(string $method, string $path, ?array $body = null, ?string $token = null): ResponseInterface
    {
        $request = $this->createRequest($method, $path, $body, $token);
        return $this->app->handle($request);
    }

    protected function createRequestWithIp(string $method, string $path, string $ip, ?array $body = null, ?string $token = null): ServerRequestInterface
    {
        $uri = (new UriFactory())->createUri($path);
        $headers = ['Content-Type' => 'application/json'];

        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $serverRequest = (new ServerRequestFactory())->createServerRequest($method, $uri, ['REMOTE_ADDR' => $ip]);

        if ($body !== null) {
            $stream = (new StreamFactory())->createStream(json_encode($body));
            $serverRequest = $serverRequest->withBody($stream);
        }

        foreach ($headers as $name => $value) {
            $serverRequest = $serverRequest->withHeader($name, $value);
        }

        return $serverRequest;
    }

    protected function handleWithIp(string $method, string $path, string $ip, ?array $body = null, ?string $token = null): ResponseInterface
    {
        $request = $this->createRequestWithIp($method, $path, $ip, $body, $token);
        return $this->app->handle($request);
    }

    protected function getAuthToken(): string
    {
        $response = $this->handle('POST', '/auth/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $body = json_decode((string) $response->getBody(), true);
        return $body['data']['access_token'] ?? $body['data']['token'] ?? null;
    }

    protected function assertStatusCode(int $expected, ResponseInterface $response, string $message = ''): void
    {
        $this->assertEquals($expected, $response->getStatusCode(), $message ?: 'Expected status code ' . $expected);
    }

    protected function getJsonBody(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true) ?: [];
    }
}
