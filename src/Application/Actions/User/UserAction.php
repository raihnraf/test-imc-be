<?php

declare(strict_types=1);

namespace Imc\Application\Actions\User;

use Imc\Application\Actions\BaseAction;
use Imc\Application\Helpers\PaginationHelper;
use Imc\Domain\Exceptions\DuplicateEntryException;
use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Domain\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserAction extends BaseAction
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
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

        $filters = [];
        if (!empty($params['search'])) {
            $filters['search'] = $params['search'];
        }
        if (isset($params['is_active']) && $params['is_active'] !== '') {
            $filters['is_active'] = $params['is_active'];
        }
        if (!empty($params['level_id'])) {
            $filters['level_id'] = $params['level_id'];
        }

        $builder = $this->userRepository->findAll($filters);
        $result = PaginationHelper::paginate($builder, $page, $perPage);

        $result['data'] = array_map(fn ($row) => $this->mapRowToArray($row), $result['data']->toArray());

        return $this->jsonResponse($response, $result);
    }

    private function get(Request $request, Response $response, int $id): Response
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return $this->notFoundResponse($response, 'User not found');
        }

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($user)]);
    }

    private function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (empty($body) || !is_array($body)) {
            return $this->validationErrorResponse($response, [
                'nama_lengkap' => ['Nama lengkap is required'],
                'username' => ['Username is required'],
                'email' => ['Email is required'],
                'password' => ['Password is required'],
            ]);
        }

        $errors = $this->validateFields($body, false);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $username = trim($body['username']);
        if ($this->userRepository->existsByUsername($username)) {
            throw new DuplicateEntryException('Username already taken', 'username');
        }

        $email = trim($body['email']);
        if ($this->userRepository->existsByEmail($email)) {
            throw new DuplicateEntryException('Email already taken', 'email');
        }

        if (!empty($body['level_id'])) {
            $level = $this->levelRepository->findById((int) $body['level_id']);
            if ($level === null) {
                return $this->validationErrorResponse($response, [
                    'level_id' => ['Level not found'],
                ]);
            }
        }

        $data = [
            'nama_lengkap' => trim($body['nama_lengkap']),
            'username' => $username,
            'email' => $email,
            'password' => $body['password'],
        ];

        if (array_key_exists('level_id', $body)) {
            $data['level_id'] = $body['level_id'] !== '' && $body['level_id'] !== null ? (int) $body['level_id'] : null;
        }
        if (array_key_exists('is_active', $body)) {
            $data['is_active'] = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $user = $this->userRepository->create($data);

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($user)], 201);
    }

    private function update(Request $request, Response $response, ?int $id): Response
    {
        if ($id === null) {
            return $this->notFoundResponse($response, 'User not found');
        }

        $existing = $this->userRepository->findById($id);
        if ($existing === null) {
            return $this->notFoundResponse($response, 'User not found');
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
        foreach (['nama_lengkap', 'username', 'email', 'password', 'level_id', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $value = $body[$field];
                if ($field === 'is_active') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                } elseif ($field === 'level_id') {
                    $value = $value !== '' && $value !== null ? (int) $value : null;
                }
                $data[$field] = $value;
            }
        }

        if (empty($data)) {
            return $this->validationErrorResponse($response, [
                'body' => ['No fields to update'],
            ]);
        }

        $user = $this->userRepository->update($id, $data);

        return $this->jsonResponse($response, ['data' => $this->mapEntityToArray($user)]);
    }

    private function delete(Request $request, Response $response, ?int $id): Response
    {
        if ($id === null) {
            return $this->notFoundResponse($response, 'User not found');
        }

        $deleted = $this->userRepository->delete($id);

        if (!$deleted) {
            return $this->notFoundResponse($response, 'User not found');
        }

        return $this->jsonResponse($response, ['message' => 'User deleted']);
    }

    private function validateFields(array $body, bool $isUpdate, ?int $excludeId = null): array
    {
        $errors = [];

        if (array_key_exists('nama_lengkap', $body)) {
            $value = $body['nama_lengkap'];
            if (!is_string($value)) {
                $errors['nama_lengkap'] = ['Nama lengkap must be a string'];
            } else {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $errors['nama_lengkap'] = ['Nama lengkap cannot be empty'];
                } elseif (mb_strlen($trimmed) > 150) {
                    $errors['nama_lengkap'] = ['Nama lengkap must not exceed 150 characters'];
                }
            }
        } elseif (!$isUpdate) {
            $errors['nama_lengkap'] = ['Nama lengkap is required'];
        }

        if (array_key_exists('username', $body)) {
            $value = $body['username'];
            if (!is_string($value)) {
                $errors['username'] = ['Username must be a string'];
            } else {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $errors['username'] = ['Username cannot be empty'];
                } elseif (mb_strlen($trimmed) < 3) {
                    $errors['username'] = ['Username must be at least 3 characters'];
                } elseif (mb_strlen($trimmed) > 50) {
                    $errors['username'] = ['Username must not exceed 50 characters'];
                } elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $trimmed)) {
                    $errors['username'] = ['Username must start with a letter and contain only letters, numbers, and underscores'];
                } elseif ($excludeId !== null && $this->userRepository->existsByUsername($trimmed, $excludeId)) {
                    $errors['username'] = ['Username already taken'];
                }
            }
        } elseif (!$isUpdate) {
            $errors['username'] = ['Username is required'];
        }

        if (array_key_exists('email', $body)) {
            $value = $body['email'];
            if (!is_string($value)) {
                $errors['email'] = ['Email must be a string'];
            } else {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $errors['email'] = ['Email cannot be empty'];
                } elseif (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = ['Email format is invalid'];
                } elseif (mb_strlen($trimmed) > 100) {
                    $errors['email'] = ['Email must not exceed 100 characters'];
                } elseif ($excludeId !== null && $this->userRepository->existsByEmail($trimmed, $excludeId)) {
                    $errors['email'] = ['Email already taken'];
                }
            }
        } elseif (!$isUpdate) {
            $errors['email'] = ['Email is required'];
        }

        if (array_key_exists('password', $body)) {
            $value = $body['password'];
            if (!is_string($value)) {
                $errors['password'] = ['Password must be a string'];
            } elseif (strlen($value) < 6) {
                $errors['password'] = ['Password must be at least 6 characters'];
            }
        } elseif (!$isUpdate) {
            $errors['password'] = ['Password is required'];
        }

        if (array_key_exists('level_id', $body) && $body['level_id'] !== null && $body['level_id'] !== '') {
            $value = $body['level_id'];
            if (!is_numeric($value) || (int) $value != $value) {
                $errors['level_id'] = ['Level ID must be an integer'];
            } else {
                $level = $this->levelRepository->findById((int) $value);
                if ($level === null) {
                    $errors['level_id'] = ['Level not found'];
                }
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
            'nama_lengkap' => $row->nama_lengkap,
            'username' => $row->username,
            'email' => $row->email,
            'level_id' => isset($row->level_id) ? (int) $row->level_id : null,
            'is_active' => (bool) $row->is_active,
            'created_at' => $row->created_at ?? null,
            'updated_at' => $row->updated_at ?? null,
        ];
    }

    private function mapEntityToArray(\Imc\Domain\User\User $user): array
    {
        return [
            'id' => $user->id,
            'nama_lengkap' => $user->namaLengkap,
            'username' => $user->username,
            'email' => $user->email,
            'level_id' => $user->levelId,
            'is_active' => $user->isActive,
            'created_at' => $user->createdAt,
            'updated_at' => $user->updatedAt,
        ];
    }
}
