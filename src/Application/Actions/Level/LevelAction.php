<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Level;

use Imc\Application\Actions\BaseAction;
use Imc\Application\Actions\DispatchByMethod;
use Imc\Application\Helpers\PaginationHelper;
use Imc\Application\Validation\LevelValidator;
use Imc\Domain\Exceptions\DuplicateEntryException;
use Imc\Domain\Exceptions\ResourceInUseException;
use Imc\Domain\Level\Level;
use Imc\Domain\Level\LevelRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LevelAction extends BaseAction
{
    use DispatchByMethod;

    public function __construct(
        private LevelRepositoryInterface $levelRepository,
        private LevelValidator $validator,
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        return $this->dispatch($request, $response, $args);
    }

    protected function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();

        $page = (int) ($params['page'] ?? 1);
        $perPage = (int) ($params['per_page'] ?? 15);
        $search = $params['search'] ?? null;
        $isActive = $params['is_active'] ?? null;

        $filters = [];
        if ($search !== null && $search !== '') {
            $filters['search'] = $search;
        }
        if ($isActive !== null && $isActive !== '') {
            $filters['is_active'] = $isActive;
        }

        $paginated = $this->levelRepository->findPaginated($filters, $page, $perPage);

        $data = array_map(fn (Level $level) => $level->toApiResponse(), $paginated->items);

        return $this->jsonResponse($response, PaginationHelper::format($data, $paginated));
    }

    protected function get(Request $request, Response $response, int $id): Response
    {
        $level = $this->levelRepository->findById($id);

        if ($level === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        return $this->jsonResponse($response, ['data' => $level->toApiResponse()]);
    }

    protected function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (empty($body) || !is_array($body)) {
            return $this->validationErrorResponse($response, [
                'name' => ['Name is required'],
            ]);
        }

        $errors = $this->validator->validate($body, false);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $name = trim($body['name']);
        if ($this->levelRepository->existsByNama($name)) {
            throw new DuplicateEntryException('Name already exists', 'name');
        }

        $data = [
            'name' => $name,
        ];

        if (array_key_exists('description', $body) && $body['description'] !== null) {
            $data['description'] = $body['description'];
        }
        if (array_key_exists('is_active', $body)) {
            $data['is_active'] = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $level = $this->levelRepository->create($data);

        return $this->jsonResponse($response, ['data' => $level->toApiResponse()], 201);
    }

    protected function update(Request $request, Response $response, ?int $id): Response
    {
        if ($id === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        $existing = $this->levelRepository->findById($id);
        if ($existing === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        $body = $request->getParsedBody();

        if (empty($body) || !is_array($body)) {
            return $this->validationErrorResponse($response, [
                'body' => ['No fields to update'],
            ]);
        }

        $errors = $this->validator->validate($body, true);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $data = [];
        if (array_key_exists('name', $body)) {
            $trimmed = trim($body['name']);
            if ($trimmed !== $existing->name && $this->levelRepository->existsByNama($trimmed, $id)) {
                throw new DuplicateEntryException('Name already exists', 'name');
            }
            $data['name'] = $trimmed;
        }
        if (array_key_exists('description', $body)) {
            $data['description'] = $body['description'];
        }
        if (array_key_exists('is_active', $body)) {
            $data['is_active'] = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        if (empty($data)) {
            return $this->validationErrorResponse($response, [
                'body' => ['No fields to update'],
            ]);
        }

        $level = $this->levelRepository->update($id, $data);

        return $this->jsonResponse($response, ['data' => $level->toApiResponse()]);
    }

    protected function delete(Request $request, Response $response, ?int $id): Response
    {
        if ($id === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        $existing = $this->levelRepository->findById($id);
        if ($existing === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        $activeUserCount = $this->levelRepository->countActiveUsers($id);
        if ($activeUserCount > 0) {
            throw new ResourceInUseException(
                "Cannot delete level. {$activeUserCount} active user(s) are assigned to this level.",
                'level_id'
            );
        }

        $deleted = $this->levelRepository->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        return $this->jsonResponse($response, ['message' => 'Level deleted']);
    }
}
