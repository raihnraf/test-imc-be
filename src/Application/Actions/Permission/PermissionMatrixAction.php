<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Permission;

use Imc\Application\Actions\BaseAction;
use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Domain\Permission\PermissionRepositoryInterface;
use Imc\Domain\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PermissionMatrixAction extends BaseAction
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepo,
        private readonly LevelRepositoryInterface $levelRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $requestingUserId = (int) $request->getAttribute('user_id');
        $requestingLevelId = (int) $request->getAttribute('level_id');
        $isAdmin = $this->levelRepository->isSuperAdmin($requestingLevelId);

        $params = $request->getQueryParams();
        $levelId = isset($params['level_id']) ? (int) $params['level_id'] : null;
        $userId = isset($params['user_id']) ? (int) $params['user_id'] : null;

        if ($levelId !== null && $userId !== null) {
            return $this->validationErrorResponse($response, [
                'query' => ['Provide either level_id or user_id, not both'],
            ]);
        }

        if ($levelId !== null) {
            if (!$isAdmin) {
                return $this->errorResponse($response, 'FORBIDDEN', 'Admin access required to view level permissions', 403);
            }

            $level = $this->levelRepository->findById($levelId);
            if ($level === null) {
                return $this->notFoundResponse($response, 'Level not found');
            }

            $matrix = $this->permissionRepo->getLevelMatrix($levelId);

            return $this->jsonResponse($response, [
                'data' => $matrix,
                'type' => 'level',
                'level_id' => $levelId,
            ]);
        }

        if ($userId !== null) {
            if (!$isAdmin && $userId !== $requestingUserId) {
                return $this->errorResponse($response, 'FORBIDDEN', 'You can only view your own permissions', 403);
            }

            $user = $this->userRepository->findById($userId);
            if ($user === null) {
                return $this->notFoundResponse($response, 'User not found');
            }

            $matrix = $this->permissionRepo->getUserMatrix($userId, $user->levelId);

            return $this->jsonResponse($response, [
                'data' => $matrix,
                'type' => 'user',
                'user_id' => $userId,
            ]);
        }

        if (!$isAdmin) {
            return $this->errorResponse($response, 'FORBIDDEN', 'Admin access required', 403);
        }

        $pages = $this->permissionRepo->getAllPages();

        return $this->jsonResponse($response, [
            'data' => array_map(fn (array $p): array => [
                ...$p,
                'has_access' => null,
            ], $pages),
            'type' => 'all',
        ]);
    }
}
