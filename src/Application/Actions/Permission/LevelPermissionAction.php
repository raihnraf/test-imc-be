<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Permission;

use Imc\Application\Actions\BaseAction;
use Imc\Domain\Permission\PermissionRepositoryInterface;
use Imc\Domain\Level\LevelRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LevelPermissionAction extends BaseAction
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepo,
        private readonly LevelRepositoryInterface $levelRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $method = $request->getMethod();
        $levelId = (int) $args['levelId'];

        return match ($method) {
            'GET' => $this->getMatrix($request, $response, $levelId),
            'POST' => $this->assign($request, $response, $levelId),
            'DELETE' => $this->remove($request, $response, $levelId),
            default => $this->errorResponse($response, 'METHOD_NOT_ALLOWED', 'Method not allowed', 405),
        };
    }

    private function assign(Request $request, Response $response, int $levelId): Response
    {
        $level = $this->levelRepository->findById($levelId);
        if ($level === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        $body = $request->getParsedBody();
        $pageId = $body['page_id'] ?? null;

        if ($pageId === null || !is_int($pageId)) {
            return $this->validationErrorResponse($response, [
                'page_id' => ['page_id is required and must be an integer'],
            ]);
        }

        try {
            $this->permissionRepo->assignLevelPermission($levelId, $pageId);
        } catch (\PDOException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'foreign key')) {
                return $this->notFoundResponse($response, 'Page not found');
            }
            if (str_contains($message, 'unique') || str_contains($message, 'duplicate')) {
                return $this->errorResponse($response, 'DUPLICATE_ENTRY', 'Permission already assigned', 409);
            }
            throw $e;
        }

        return $this->jsonResponse($response, ['message' => 'Permission assigned'], 201);
    }

    private function remove(Request $request, Response $response, int $levelId): Response
    {
        $level = $this->levelRepository->findById($levelId);
        if ($level === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        $body = $request->getParsedBody();
        $pageId = $body['page_id'] ?? null;

        if ($pageId === null || !is_int($pageId)) {
            return $this->validationErrorResponse($response, [
                'page_id' => ['page_id is required and must be an integer'],
            ]);
        }

        $removed = $this->permissionRepo->removeLevelPermission($levelId, $pageId);

        if (!$removed) {
            return $this->notFoundResponse($response, 'Permission not found');
        }

        return $this->jsonResponse($response, ['message' => 'Permission removed'], 200);
    }

    private function getMatrix(Request $request, Response $response, int $levelId): Response
    {
        $level = $this->levelRepository->findById($levelId);
        if ($level === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        $matrix = $this->permissionRepo->getLevelMatrix($levelId);

        return $this->jsonResponse($response, ['data' => $matrix], 200);
    }
}
