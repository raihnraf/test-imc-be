<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Auth;

use Imc\Application\Actions\BaseAction;
use Imc\Domain\Exceptions\AuthenticationException;
use Imc\Domain\RateLimit\RateLimitRepositoryInterface;
use Imc\Domain\RefreshToken\RefreshTokenRepositoryInterface;
use Imc\Domain\Token\TokenService;
use Imc\Domain\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginAction extends BaseAction
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TokenService $tokenService,
        private RefreshTokenRepositoryInterface $refreshTokenRepo,
        private RateLimitRepositoryInterface $rateLimitRepo,
        private array $settings,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $username = $body['username'] ?? null;
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;

        // At least username or email required
        if (empty($username) && empty($email)) {
            return $this->validationErrorResponse($response, [
                'username' => ['Username or email is required'],
            ]);
        }

        if (empty($password)) {
            return $this->validationErrorResponse($response, [
                'password' => ['Password is required'],
            ]);
        }

        // Find user by username or email
        $login = $username ?? $email;
        $user = $this->userRepository->findByUsernameOrEmail($login);

        if ($user === null) {
            $this->rateLimitRepo->recordAttempt($this->getClientIp($request));
            throw new AuthenticationException(
                'Invalid username/email or password',
                'INVALID_CREDENTIALS',
                401
            );
        }

        // Check active
        if (!$user->isActive) {
            throw new AuthenticationException(
                'Account is deactivated',
                'ACCOUNT_INACTIVE',
                401
            );
        }

        // Verify password
        if (!password_verify($password, $user->password)) {
            $this->rateLimitRepo->recordAttempt($this->getClientIp($request));
            throw new AuthenticationException(
                'Invalid username/email or password',
                'INVALID_CREDENTIALS',
                401
            );
        }

        $accessExpiry = (int) $this->settings['jwt']['access_token_expiry'];
        $refreshExpiry = (int) $this->settings['jwt']['refresh_token_expiry'];

        // Generate JWT access token
        $accessToken = $this->tokenService->generateToken([
            'user_id' => $user->id,
            'level_id' => $user->levelId,
            'username' => $user->username,
        ], $accessExpiry);

        // Generate refresh token
        $refreshData = $this->tokenService->generateRefreshToken();
        $expiresAt = new \DateTimeImmutable('+' . $refreshExpiry . ' seconds');
        $this->refreshTokenRepo->store($user->id, $refreshData['hash'], $expiresAt);

        return $this->jsonResponse($response, [
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshData['raw_token'],
                'token_type' => 'Bearer',
                'expires_in' => $accessExpiry,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'full_name' => $user->fullName,
                    'level_id' => $user->levelId,
                ],
            ],
        ]);
    }

    private function getClientIp(Request $request): string
    {
        return $request->getServerParams()['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
