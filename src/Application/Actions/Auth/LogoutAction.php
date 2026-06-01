<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Auth;

use Imc\Application\Actions\BaseAction;
use Imc\Domain\RefreshToken\RefreshTokenRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogoutAction extends BaseAction
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokenRepo,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('user_id');

        $revokedCount = $this->refreshTokenRepo->revokeAllForUser($userId);

        return $this->jsonResponse($response, [
            'statusCode' => 200,
            'message' => 'Successfully logged out',
            'sessions_revoked' => $revokedCount,
        ]);
    }
}
