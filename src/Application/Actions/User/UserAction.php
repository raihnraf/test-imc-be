<?php

declare(strict_types=1);

namespace Imc\Application\Actions\User;

use Imc\Application\Actions\BaseAction;
use Imc\Application\Actions\DispatchByMethod;
use Imc\Application\Helpers\PaginationHelper;
use Imc\Application\Validation\UserValidator;
use Imc\Domain\Exceptions\DuplicateEntryException;
use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Domain\User\User;
use Imc\Domain\User\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserAction extends BaseAction
{
    use DispatchByMethod;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private LevelRepositoryInterface $levelRepository,
        private UserValidator $validator,
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

        $paginated = $this->userRepository->findPaginated($filters, $page, $perPage);

        $data = array_map(fn (User $user) => $user->toApiResponse(), $paginated->items);

        return $this->jsonResponse($response, PaginationHelper::format($data, $paginated));
    }

    protected function get(Request $request, Response $response, int $id): Response
    {
        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return $this->notFoundResponse($response, 'User not found');
        }

        return $this->jsonResponse($response, ['data' => $user->toApiResponse()]);
    }

    protected function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (empty($body) || !is_array($body)) {
            return $this->validationErrorResponse($response, [
                'full_name' => ['Full name is required'],
                'username' => ['Username is required'],
                'email' => ['Email is required'],
                'password' => ['Password is required'],
            ]);
        }

        $errors = $this->validator->validate($body, false);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $username = trim($body['username']);
        $email = trim($body['email']);

        if (!empty($body['level_id'])) {
            $level = $this->levelRepository->findById((int) $body['level_id']);
            if ($level === null) {
                return $this->validationErrorResponse($response, [
                    'level_id' => ['Level not found'],
                ]);
            }
        }

        $data = [
            'full_name' => trim($body['full_name']),
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

        return $this->jsonResponse($response, ['data' => $user->toApiResponse()], 201);
    }

    protected function update(Request $request, Response $response, ?int $id): Response
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

        $errors = $this->validator->validate($body, true, $id);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $data = [];
        foreach (['full_name', 'username', 'email', 'password', 'level_id', 'is_active'] as $field) {
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

        return $this->jsonResponse($response, ['data' => $user->toApiResponse()]);
    }

    protected function delete(Request $request, Response $response, ?int $id): Response
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
}
