<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Auth;

use Imc\Application\Actions\BaseAction;
use Imc\Domain\Exceptions\AuthenticationException;
use Imc\Domain\RefreshToken\RefreshTokenRepositoryInterface;
use Imc\Domain\Token\TokenService;
use Imc\Domain\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RefreshTokenAction extends BaseAction
{
    public function __construct(
        private TokenService $tokenService,
        private RefreshTokenRepositoryInterface $refreshTokenRepo,
        private UserRepositoryInterface $userRepository,
        private array $settings,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $rawToken = $body['refresh_token'] ?? null;

        if (empty($rawToken)) {
            return $this->validationErrorResponse($response, [
                'refresh_token' => ['Refresh token is required'],
            ]);
        }

        $receivedHash = hash('sha256', $rawToken);
        $row = $this->refreshTokenRepo->findByHash($receivedHash);

        if ($row === null) {
            throw new AuthenticationException('Invalid refresh token', 'INVALID_TOKEN');
        }

        if (strtotime($row->expires_at) < time()) {
            throw new AuthenticationException('Refresh token has expired', 'TOKEN_EXPIRED');
        }

        if ($row->revoked_at !== null) {
            throw new AuthenticationException('Refresh token has been revoked', 'TOKEN_REVOKED');
        }

        $user = $this->userRepository->findById((int) $row->user_id);
        if ($user === null) {
            throw new AuthenticationException('User not found', 'INTERNAL_ERROR', 500);
        }

        if (!$user->isActive) {
            throw new AuthenticationException('Account is deactivated', 'ACCOUNT_INACTIVE');
        }

        $accessExpiry = (int) ($this->settings['jwt']['access_token_expiry'] ?? 900);
        $refreshExpiry = (int) ($this->settings['jwt']['refresh_token_expiry'] ?? 604800);

        $accessToken = $this->tokenService->generateToken([
            'user_id' => $user->id,
            'level_id' => $user->levelId,
            'username' => $user->username,
        ], $accessExpiry);

        $refreshData = $this->tokenService->generateRefreshToken();
        $expiresAt = new \DateTimeImmutable('+' . $refreshExpiry . ' seconds');

        $this->refreshTokenRepo->rotateToken((int) $row->id, $user->id, $refreshData['hash'], $expiresAt);

        return $this->jsonResponse($response, [
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshData['raw_token'],
                'token_type' => 'Bearer',
                'expires_in' => $accessExpiry,
            ],
        ]);
    }
}
