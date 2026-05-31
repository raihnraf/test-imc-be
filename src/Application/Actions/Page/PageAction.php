<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Page;

use Imc\Application\Actions\BaseAction;
use Imc\Application\Helpers\PaginationHelper;
use Imc\Domain\Exceptions\DuplicateEntryException;
use Imc\Domain\Page\PageRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PageAction extends BaseAction
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
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

        $filters = [];
        if (!empty($params['search'])) {
            $filters['search'] = $params['search'];
        }
        if (isset($params['is_active']) && $params['is_active'] !== '') {
            $filters['is_active'] = $params['is_active'];
        }

        $builder = $this->pageRepository->findAll($filters);
        $result = PaginationHelper::paginate($builder, $page, $perPage);

        $result['data'] = array_map(fn ($row) => $this->mapRowToArray($row), $result['data']->toArray());

        return $this->jsonResponse($response, $result);
    }

    private function get(Request $request, Response $response, int $id): Response
    {
        $page = $this->pageRepository->findById($id);

        if ($page === null) {
            return $this->notFoundResponse($response, 'Page not found');
        }

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($page)]);
    }

    private function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (empty($body) || !is_array($body)) {
            return $this->validationErrorResponse($response, [
                'nama_page' => ['Nama page is required'],
                'route_path' => ['Route path is required'],
            ]);
        }

        $errors = $this->validateFields($body, false);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $routePath = trim($body['route_path']);
        if ($this->pageRepository->existsByRoute($routePath)) {
            throw new DuplicateEntryException('Route path already exists', 'route_path');
        }

        $data = [
            'nama_page' => trim($body['nama_page']),
            'route_path' => $routePath,
        ];

        if (array_key_exists('deskripsi', $body) && $body['deskripsi'] !== null) {
            $data['deskripsi'] = $body['deskripsi'];
        }
        if (array_key_exists('urutan_tampil', $body)) {
            $data['urutan_tampil'] = (int) $body['urutan_tampil'];
        }
        if (array_key_exists('is_active', $body)) {
            $data['is_active'] = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $page = $this->pageRepository->create($data);

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($page)], 201);
    }

    private function update(Request $request, Response $response, ?int $id): Response
    {
        if ($id === null) {
            return $this->notFoundResponse($response, 'Page not found');
        }

        $existing = $this->pageRepository->findById($id);
        if ($existing === null) {
            return $this->notFoundResponse($response, 'Page not found');
        }

        $body = $request->getParsedBody();

        if (empty($body) || !is_array($body)) {
            return $this->validationErrorResponse($response, [
                'body' => ['No fields to update'],
            ]);
        }

        $errors = $this->validateFields($body, true, $id);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $data = [];
        foreach (['nama_page', 'route_path', 'deskripsi', 'urutan_tampil', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $value = $body[$field];
                if ($field === 'is_active') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                } elseif ($field === 'urutan_tampil') {
                    $value = (int) $value;
                }
                $data[$field] = $value;
            }
        }

        if (empty($data)) {
            return $this->validationErrorResponse($response, [
                'body' => ['No fields to update'],
            ]);
        }

        $page = $this->pageRepository->update($id, $data);

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($page)]);
    }

    private function delete(Request $request, Response $response, ?int $id): Response
    {
        if ($id === null) {
            return $this->notFoundResponse($response, 'Page not found');
        }

        $deleted = $this->pageRepository->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse($response, 'Page not found');
        }

        return $this->jsonResponse($response, ['message' => 'Page deleted']);
    }

    private function validateFields(array $body, bool $isUpdate, ?int $excludeId = null): array
    {
        $errors = [];

        if (array_key_exists('nama_page', $body)) {
            $value = $body['nama_page'];
            if (!is_string($value)) {
                $errors['nama_page'] = ['Nama page must be a string'];
            } else {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $errors['nama_page'] = ['Nama page cannot be empty'];
                } elseif (mb_strlen($trimmed) > 100) {
                    $errors['nama_page'] = ['Nama page must not exceed 100 characters'];
                }
            }
        } elseif (!$isUpdate) {
            $errors['nama_page'] = ['Nama page is required'];
        }

        if (array_key_exists('route_path', $body)) {
            $value = $body['route_path'];
            if (!is_string($value)) {
                $errors['route_path'] = ['Route path must be a string'];
            } else {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $errors['route_path'] = ['Route path cannot be empty'];
                } elseif (mb_strlen($trimmed) > 255) {
                    $errors['route_path'] = ['Route path must not exceed 255 characters'];
                } elseif (!str_starts_with($trimmed, '/')) {
                    $errors['route_path'] = ['Route path must start with "/"'];
                } elseif (preg_match('/\s/', $trimmed)) {
                    $errors['route_path'] = ['Route path must not contain spaces'];
                } elseif ($excludeId !== null && $this->pageRepository->existsByRoute($trimmed, $excludeId)) {
                    $errors['route_path'] = ['Route path already exists'];
                }
            }
        } elseif (!$isUpdate) {
            $errors['route_path'] = ['Route path is required'];
        }

        if (array_key_exists('deskripsi', $body) && $body['deskripsi'] !== null && !is_string($body['deskripsi'])) {
            $errors['deskripsi'] = ['Deskripsi must be a string'];
        }

        if (array_key_exists('urutan_tampil', $body)) {
            $value = $body['urutan_tampil'];
            if (!is_numeric($value) || (int) $value < 0) {
                $errors['urutan_tampil'] = ['Urutan tampil must be a non-negative integer'];
            }
        }

        if (array_key_exists('is_active', $body)) {
            $result = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($result === null) {
                $errors['is_active'] = ['is_active must be a boolean value'];
            }
        }

        return $errors;
    }

    private function mapRowToArray(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'nama_page' => $row->nama_page,
            'route_path' => $row->route_path,
            'deskripsi' => $row->deskripsi ?? null,
            'urutan_tampil' => (int) $row->urutan_tampil,
            'is_active' => (bool) $row->is_active,
            'created_at' => $row->created_at ?? null,
            'updated_at' => $row->updated_at ?? null,
        ];
    }

    private function mapEntityToArray(\Imc\Domain\Page\Page $page): array
    {
        return [
            'id' => $page->id,
            'nama_page' => $page->namaPage,
            'route_path' => $page->routePath,
            'deskripsi' => $page->deskripsi,
            'urutan_tampil' => $page->urutanTampil,
            'is_active' => $page->isActive,
            'created_at' => $page->createdAt,
            'updated_at' => $page->updatedAt,
        ];
    }
}
