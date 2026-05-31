<?php

declare(strict_types=1);

namespace Imc\Application\Actions\Page;

use Imc\Application\Actions\BaseAction;
use Imc\Application\Actions\DispatchByMethod;
use Imc\Application\Helpers\PaginationHelper;
use Imc\Application\Validation\PageValidator;
use Imc\Domain\Exceptions\DuplicateEntryException;
use Imc\Domain\Page\Page;
use Imc\Domain\Page\PageRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PageAction extends BaseAction
{
    use DispatchByMethod;

    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private PageValidator $validator,
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

        $paginated = $this->pageRepository->findPaginated($filters, $page, $perPage);

        $data = array_map(fn (Page $page) => $page->toApiResponse(), $paginated->items);

        return $this->jsonResponse($response, PaginationHelper::format($data, $paginated));
    }

    protected function get(Request $request, Response $response, int $id): Response
    {
        $page = $this->pageRepository->findById($id);

        if ($page === null) {
            return $this->notFoundResponse($response, 'Page not found');
        }

        return $this->jsonResponse($response, ['data' => $page->toApiResponse()]);
    }

    protected function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (empty($body) || !is_array($body)) {
            return $this->validationErrorResponse($response, [
                'name' => ['Name is required'],
                'route_path' => ['Route path is required'],
            ]);
        }

        $errors = $this->validator->validate($body, false);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $routePath = trim($body['route_path']);
        if ($this->pageRepository->existsByRoute($routePath)) {
            throw new DuplicateEntryException('Route path already exists', 'route_path');
        }

        $data = [
            'name' => trim($body['name']),
            'route_path' => $routePath,
        ];

        if (array_key_exists('description', $body) && $body['description'] !== null) {
            $data['description'] = $body['description'];
        }
        if (array_key_exists('display_order', $body)) {
            $data['display_order'] = (int) $body['display_order'];
        }
        if (array_key_exists('is_active', $body)) {
            $data['is_active'] = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $page = $this->pageRepository->create($data);

        return $this->jsonResponse($response, ['data' => $page->toApiResponse()], 201);
    }

    protected function update(Request $request, Response $response, ?int $id): Response
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

        $errors = $this->validator->validate($body, true, $id);

        if (!empty($errors)) {
            return $this->validationErrorResponse($response, $errors);
        }

        $data = [];
        foreach (['name', 'route_path', 'description', 'display_order', 'is_active'] as $field) {
            if (array_key_exists($field, $body)) {
                $value = $body[$field];
                if ($field === 'is_active') {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                } elseif ($field === 'display_order') {
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

        return $this->jsonResponse($response, ['data' => $page->toApiResponse()]);
    }

    protected function delete(Request $request, Response $response, ?int $id): Response
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
}
