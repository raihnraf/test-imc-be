<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Permission;

use Imc\Application\Actions\BaseAction;
use Imc\Domain\Permission\PermissionRepositoryInterface;
use Imc\Domain\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserPermissionAction extends BaseAction
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepo,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $method = $request->getMethod();
        $userId = (int) $args['userId'];

        return match ($method) {
            'GET' => $this->getMatrix($request, $response, $userId),
            'POST' => $this->assign($request, $response, $userId),
            'DELETE' => $this->remove($request, $response, $userId),
            default => $this->errorResponse($response, 'METHOD_NOT_ALLOWED', 'Method not allowed', 405),
        };
    }

    private function assign(Request $request, Response $response, int $userId): Response
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return $this->notFoundResponse($response, 'User not found');
        }

        $body = $request->getParsedBody();

        $pageId = $body['page_id'] ?? null;
        if ($pageId === null || !is_int($pageId)) {
            return $this->validationErrorResponse($response, [
                'page_id' => ['page_id is required and must be an integer'],
            ]);
        }

        $rawIsGranted = $body['is_granted'] ?? null;
        $isGranted = filter_var($rawIsGranted, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isGranted === null) {
            return $this->validationErrorResponse($response, [
                'is_granted' => ['is_granted is required and must be a boolean'],
            ]);
        }

        try {
            $this->permissionRepo->assignUserPermission($userId, $pageId, $isGranted);
        } catch (\PDOException $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'foreign key')) {
                return $this->notFoundResponse($response, 'Page not found');
            }
            throw $e;
        }

        $message = $isGranted ? 'Permission granted' : 'Permission denied';

        return $this->jsonResponse($response, ['message' => $message], 201);
    }

    private function remove(Request $request, Response $response, int $userId): Response
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return $this->notFoundResponse($response, 'User not found');
        }

        $body = $request->getParsedBody();
        $pageId = $body['page_id'] ?? null;

        if ($pageId === null || !is_int($pageId)) {
            return $this->validationErrorResponse($response, [
                'page_id' => ['page_id is required and must be an integer'],
            ]);
        }

        $removed = $this->permissionRepo->removeUserPermission($userId, $pageId);

        if (!$removed) {
            return $this->notFoundResponse($response, 'Permission override not found');
        }

        return $this->jsonResponse($response, ['message' => 'Permission override removed'], 200);
    }

    private function getMatrix(Request $request, Response $response, int $userId): Response
    {
        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            return $this->notFoundResponse($response, 'User not found');
        }

        $matrix = $this->permissionRepo->getUserMatrix($userId, $user->levelId);

        return $this->jsonResponse($response, ['data' => $matrix], 200);
    }
}
