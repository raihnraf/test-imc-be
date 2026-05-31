<?php

declare(strict_types=1);

namespace Imc\Application\Validation;

use Imc\Domain\Page\PageRepositoryInterface;

class PageValidator
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
    ) {
    }

    public function validate(array $body, bool $isUpdate, ?int $excludeId = null): array
    {
        $errors = [];

        if (array_key_exists('name', $body)) {
            $value = $body['name'];
            if (!is_string($value)) {
                $errors['name'] = ['Name must be a string'];
            } else {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $errors['name'] = ['Name cannot be empty'];
                } elseif (mb_strlen($trimmed) > 100) {
                    $errors['name'] = ['Name must not exceed 100 characters'];
                }
            }
        } elseif (!$isUpdate) {
            $errors['name'] = ['Name is required'];
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

        if (array_key_exists('description', $body) && $body['description'] !== null && !is_string($body['description'])) {
            $errors['description'] = ['Description must be a string'];
        }

        if (array_key_exists('display_order', $body)) {
            $value = $body['display_order'];
            if (!is_numeric($value) || (int) $value < 0) {
                $errors['display_order'] = ['Display order must be a non-negative integer'];
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
}
