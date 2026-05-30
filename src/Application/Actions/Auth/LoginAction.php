<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Auth;

use Imc\Application\Actions\BaseAction;
use Imc\Domain\User\UserRepositoryInterface;
use Imc\Domain\Token\TokenService;
use Imc\Domain\Exceptions\ValidationException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginAction extends BaseAction
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TokenService $tokenService,
    ) {}

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
            return $this->errorResponse($response, 'INVALID_CREDENTIALS', 'Invalid username/email or password', 401);
        }

        // Check active
        if (!$user->isActive) {
            return $this->errorResponse($response, 'ACCOUNT_INACTIVE', 'Account is deactivated', 401);
        }

        // Verify password
        if (!password_verify($password, $user->password)) {
            return $this->errorResponse($response, 'INVALID_CREDENTIALS', 'Invalid username/email or password', 401);
        }

        // Generate JWT
        $token = $this->tokenService->generateToken([
            'user_id' => $user->id,
            'level_id' => $user->levelId,
            'username' => $user->username,
        ]);

        return $this->jsonResponse($response, [
            'statusCode' => 200,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nama_lengkap' => $user->namaLengkap,
                    'level_id' => $user->levelId,
                ],
            ],
        ]);
    }
}
