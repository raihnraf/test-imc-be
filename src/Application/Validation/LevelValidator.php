<?php

declare(strict_types=1);

namespace Imc\Application\Validation;

class LevelValidator
{
    public function validate(array $body, bool $isUpdate): array
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

        if (array_key_exists('description', $body) && $body['description'] !== null && !is_string($body['description'])) {
            $errors['description'] = ['Description must be a string'];
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
