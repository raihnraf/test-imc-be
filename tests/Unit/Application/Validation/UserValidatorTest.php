<?php

declare(strict_types=1);

namespace Imc\Tests\Unit\Application\Validation;

use Imc\Application\Validation\UserValidator;
use Imc\Domain\Level\Level;
use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Domain\User\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    private function createValidator(
        ?UserRepositoryInterface $userRepo = null,
        ?LevelRepositoryInterface $levelRepo = null,
    ): UserValidator {
        return new UserValidator(
            $userRepo ?? $this->createMock(UserRepositoryInterface::class),
            $levelRepo ?? $this->createMock(LevelRepositoryInterface::class),
        );
    }

    public function test_create_requires_all_fields(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate([], false);

        $this->assertArrayHasKey('full_name', $errors);
        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function test_update_does_not_require_missing_fields(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate([], true);

        $this->assertEmpty($errors);
    }

    public function test_full_name_empty_string_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['full_name' => ''], false);

        $this->assertArrayHasKey('full_name', $errors);
    }

    public function test_full_name_exceeds_max_length_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['full_name' => str_repeat('a', 151)], false);

        $this->assertArrayHasKey('full_name', $errors);
    }

    public function test_full_name_valid_passes(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['full_name' => 'John Doe'], false);

        $this->assertArrayNotHasKey('full_name', $errors);
    }

    public function test_username_too_short_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['username' => 'ab'], false);

        $this->assertArrayHasKey('username', $errors);
        $this->assertStringContainsString('at least 3', $errors['username'][0]);
    }

    public function test_username_exceeds_max_length_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['username' => str_repeat('a', 51)], false);

        $this->assertArrayHasKey('username', $errors);
    }

    public function test_username_starts_with_number_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['username' => '1abc'], false);

        $this->assertArrayHasKey('username', $errors);
        $this->assertStringContainsString('start with a letter', $errors['username'][0]);
    }

    public function test_username_with_special_chars_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['username' => 'user@name'], false);

        $this->assertArrayHasKey('username', $errors);
    }

    public function test_username_valid_passes(): void
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->method('existsByUsername')->willReturn(false);
        $validator = $this->createValidator($mockRepo);

        $errors = $validator->validate(['username' => 'valid_user123'], false);

        $this->assertArrayNotHasKey('username', $errors);
    }

    public function test_duplicate_username_on_create_returns_error(): void
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->method('existsByUsername')->willReturn(true);
        $validator = $this->createValidator($mockRepo);

        $errors = $validator->validate(['username' => 'takenuser'], false);

        $this->assertArrayHasKey('username', $errors);
        $this->assertStringContainsString('already taken', $errors['username'][0]);
    }

    public function test_duplicate_username_on_update_excludes_self(): void
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->expects($this->once())
            ->method('existsByUsername')
            ->with('myuser', 5)
            ->willReturn(false);
        $validator = $this->createValidator($mockRepo);

        $errors = $validator->validate(['username' => 'myuser'], true, 5);

        $this->assertArrayNotHasKey('username', $errors);
    }

    public function test_invalid_email_format_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['email' => 'not-an-email'], false);

        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString('invalid', $errors['email'][0]);
    }

    public function test_email_exceeds_max_length_returns_error(): void
    {
        $validator = $this->createValidator();

        $longEmail = str_repeat('a', 90) . '@example.com';
        $errors = $validator->validate(['email' => $longEmail], false);

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_duplicate_email_returns_error(): void
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->method('existsByEmail')->willReturn(true);
        $validator = $this->createValidator($mockRepo);

        $errors = $validator->validate(['email' => 'taken@example.com'], false);

        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString('already taken', $errors['email'][0]);
    }

    public function test_email_valid_passes(): void
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->method('existsByEmail')->willReturn(false);
        $validator = $this->createValidator($mockRepo);

        $errors = $validator->validate(['email' => 'valid@example.com'], false);

        $this->assertArrayNotHasKey('email', $errors);
    }

    public function test_password_too_short_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['password' => '12345'], false);

        $this->assertArrayHasKey('password', $errors);
        $this->assertStringContainsString('at least 6', $errors['password'][0]);
    }

    public function test_password_valid_passes(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['password' => '123456'], false);

        $this->assertArrayNotHasKey('password', $errors);
    }

    public function test_level_id_not_found_returns_error(): void
    {
        $mockLevelRepo = $this->createMock(LevelRepositoryInterface::class);
        $mockLevelRepo->method('findById')->willReturn(null);
        $validator = $this->createValidator(levelRepo: $mockLevelRepo);

        $errors = $validator->validate(['level_id' => 999], false);

        $this->assertArrayHasKey('level_id', $errors);
        $this->assertStringContainsString('not found', $errors['level_id'][0]);
    }

    public function test_level_id_valid_passes(): void
    {
        $level = new Level(id: 1, name: 'Admin');
        $mockLevelRepo = $this->createMock(LevelRepositoryInterface::class);
        $mockLevelRepo->method('findById')->willReturn($level);
        $validator = $this->createValidator(levelRepo: $mockLevelRepo);

        $errors = $validator->validate(['level_id' => 1], false);

        $this->assertArrayNotHasKey('level_id', $errors);
    }

    public function test_level_id_non_integer_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['level_id' => 'abc'], false);

        $this->assertArrayHasKey('level_id', $errors);
    }

    public function test_is_active_invalid_boolean_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['is_active' => 'maybe'], false);

        $this->assertArrayHasKey('is_active', $errors);
    }

    public function test_is_active_true_passes(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['is_active' => true], false);

        $this->assertArrayNotHasKey('is_active', $errors);
    }

    public function test_is_active_false_passes(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['is_active' => false], false);

        $this->assertArrayNotHasKey('is_active', $errors);
    }

    public function test_is_active_string_boolean_passes(): void
    {
        $validator = $this->createValidator();

        $errorsTrue = $validator->validate(['is_active' => 'true'], false);
        $errorsFalse = $validator->validate(['is_active' => 'false'], false);

        $this->assertArrayNotHasKey('is_active', $errorsTrue);
        $this->assertArrayNotHasKey('is_active', $errorsFalse);
    }

    public function test_full_validation_returns_multiple_errors(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate([
            'full_name' => '',
            'username' => 'ab',
            'email' => 'bad',
            'password' => '123',
        ], false);

        $this->assertCount(4, $errors);
    }

    public function test_partial_update_only_validates_provided_fields(): void
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->method('existsByUsername')->willReturn(false);
        $validator = $this->createValidator($mockRepo);

        $errors = $validator->validate([
            'username' => 'newname',
            'email' => 'new@example.com',
        ], true);

        $this->assertArrayNotHasKey('full_name', $errors);
        $this->assertArrayNotHasKey('password', $errors);
        $this->assertArrayNotHasKey('username', $errors);
        $this->assertArrayNotHasKey('email', $errors);
    }

    public function test_username_trimmed_before_validation(): void
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->method('existsByUsername')->willReturn(false);
        $validator = $this->createValidator($mockRepo);

        $errors = $validator->validate(['username' => '  validuser  '], false);

        $this->assertArrayNotHasKey('username', $errors);
    }

    public function test_whitespace_only_username_returns_error(): void
    {
        $validator = $this->createValidator();

        $errors = $validator->validate(['username' => '   '], false);

        $this->assertArrayHasKey('username', $errors);
    }

    public function test_email_trimmed_before_validation(): void
    {
        $mockRepo = $this->createMock(UserRepositoryInterface::class);
        $mockRepo->method('existsByEmail')->willReturn(false);
        $validator = $this->createValidator($mockRepo);

        $errors = $validator->validate(['email' => '  user@example.com  '], false);

        $this->assertArrayNotHasKey('email', $errors);
    }
}
