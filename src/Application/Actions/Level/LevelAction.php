<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Level;

use Imc\Application\Actions\BaseAction;
use Imc\Application\Helpers\PaginationHelper;
use Imc\Domain\Level\LevelRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LevelAction extends BaseAction
{
    public function __construct(
        private LevelRepositoryInterface $levelRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $method = $request->getMethod();
        $id = isset($args['id']) ? (int) $args['id'] : null;

        return match ($method) {
            'GET' => $id !== null
                ? $this->get($request, $response, $id)
                : $this->list($request, $response),
            'POST' => $this->create($request, $response),
            'PUT' => $this->update($request, $response, $id),
            'DELETE' => $this->delete($request, $response, $id),
            default => $this->errorResponse($response, 'METHOD_NOT_ALLOWED', 'Method not allowed', 405),
        };
    }

    private function list(Request $request, Response $response): Response
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

        $builder = $this->levelRepository->findAll($filters);
        $result = PaginationHelper::paginate($builder, $page, $perPage);

        $result['data'] = array_map(fn ($row) => $this->mapToArray($row), $result['data']->toArray());

        return $this->jsonResponse($response, $result);
    }

    private function get(Request $request, Response $response, int $id): Response
    {
        $level = $this->levelRepository->findById($id);

        if ($level === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($level)]);
    }

    private function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (empty($body) || !is_array($body)) {
            return $this->validationErrorResponse($response, [
                'nama_level' => ['Nama level is required'],
            ]);
        }

        $errors = $this->validateFields($body, false);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $data = [
            'nama_level' => trim($body['nama_level']),
        ];

        if (array_key_exists('deskripsi', $body) && $body['deskripsi'] !== null) {
            $data['deskripsi'] = $body['deskripsi'];
        }
        if (array_key_exists('is_active', $body)) {
            $data['is_active'] = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $level = $this->levelRepository->create($data);

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($level)], 201);
    }

    private function update(Request $request, Response $response, ?int $id): Response
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

        $errors = $this->validateFields($body, true);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $data = [];
        if (array_key_exists('nama_level', $body)) {
            $data['nama_level'] = trim($body['nama_level']);
        }
        if (array_key_exists('deskripsi', $body)) {
            $data['deskripsi'] = $body['deskripsi'];
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

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($level)]);
    }

    private function delete(Request $request, Response $response, ?int $id): Response
    {
        if ($id === null) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        $deleted = $this->levelRepository->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse($response, 'Level not found');
        }

        return $this->jsonResponse($response, ['message' => 'Level deleted']);
    }

    private function validateFields(array $body, bool $isUpdate): array
    {
        $errors = [];

        if (array_key_exists('nama_level', $body)) {
            $value = $body['nama_level'];
            if (!is_string($value)) {
                $errors['nama_level'] = ['Nama level must be a string'];
            } else {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $errors['nama_level'] = ['Nama level cannot be empty'];
                } elseif (mb_strlen($trimmed) > 100) {
                    $errors['nama_level'] = ['Nama level must not exceed 100 characters'];
                }
            }
        } elseif (!$isUpdate) {
            $errors['nama_level'] = ['Nama level is required'];
        }

        if (array_key_exists('deskripsi', $body) && $body['deskripsi'] !== null && !is_string($body['deskripsi'])) {
            $errors['deskripsi'] = ['Deskripsi must be a string'];
        }

        if (array_key_exists('is_active', $body)) {
            $result = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($result === null) {
                $errors['is_active'] = ['is_active must be a boolean value'];
            }
        }

        return $errors;
    }

    private function mapToArray(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'nama_level' => $row->nama_level,
            'deskripsi' => $row->deskripsi ?? null,
            'is_active' => (bool) $row->is_active,
            'created_at' => $row->created_at ?? null,
            'updated_at' => $row->updated_at ?? null,
        ];
    }

    private function mapEntityToArray(\Imc\Domain\Level\Level $level): array
    {
        return [
            'id' => $level->id,
            'nama_level' => $level->namaLevel,
            'deskripsi' => $level->deskripsi,
            'is_active' => $level->isActive,
            'created_at' => $level->createdAt,
            'updated_at' => $level->updatedAt,
        ];
    }
}
