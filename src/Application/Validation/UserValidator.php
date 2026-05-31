<?php

declare(strict_types=1);

namespace Imc\Application\Validation;

use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Domain\User\UserRepositoryInterface;

class UserValidator
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private LevelRepositoryInterface $levelRepository,
    ) {
    }

    public function validate(array $body, bool $isUpdate, ?int $excludeId = null): array
    {
        $errors = [];

        if (array_key_exists('full_name', $body)) {
            $value = $body['full_name'];
            if (!is_string($value)) {
                $errors['full_name'] = ['Full name must be a string'];
            } else {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $errors['full_name'] = ['Full name cannot be empty'];
                } elseif (mb_strlen($trimmed) > 150) {
                    $errors['full_name'] = ['Full name must not exceed 150 characters'];
                }
            }
        } elseif (!$isUpdate) {
            $errors['full_name'] = ['Full name is required'];
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
            if (!is_numeric($value) || (int) $value !== $value) {
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
}
