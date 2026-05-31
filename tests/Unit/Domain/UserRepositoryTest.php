<?php

declare(strict_types=1);

namespace Imc\Tests\Unit\Domain;

use Imc\Domain\User\UserRepository;
use Imc\Domain\User\UserRepositoryInterface;
use Imc\Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private string $suffix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
        $this->suffix = substr(uniqid('', true), 0, 8);
    }

    public function testFindById(): void
    {
        $user = $this->repository->findById(1);
        $this->assertNotNull($user);
        $this->assertEquals('admin', $user->username);
    }

    public function testFindByUsernameOrEmailWithUsername(): void
    {
        $user = $this->repository->findByUsernameOrEmail('admin');
        $this->assertNotNull($user);
    }

    public function testFindByUsernameOrEmailWithEmail(): void
    {
        $user = $this->repository->findByUsernameOrEmail('admin@imc.local');
        $this->assertNotNull($user);
    }

    public function testFindByUsernameOrEmailNotFound(): void
    {
        $user = $this->repository->findByUsernameOrEmail('nonexistent');
        $this->assertNull($user);
    }

    public function testFindAll(): void
    {
        $builder = $this->repository->findAll([]);
        $this->assertInstanceOf(\Illuminate\Database\Query\Builder::class, $builder);

        $results = $builder->get();
        $this->assertGreaterThan(0, count($results));
    }

    public function testFindAllWithSearch(): void
    {
        $builder = $this->repository->findAll(['search' => 'admin']);
        $results = $builder->get();
        $this->assertGreaterThan(0, count($results));
    }

    public function testFindAllWithIsActive(): void
    {
        $builder = $this->repository->findAll(['is_active' => '1']);
        $results = $builder->get();

        foreach ($results as $row) {
            $this->assertTrue((bool) $row->is_active);
        }
    }

    public function testCreateHashesPassword(): void
    {
        $user = $this->repository->create([
            'nama_lengkap' => 'Hash Test',
            'username' => "hash{$this->suffix}",
            'email' => "hash{$this->suffix}@example.com",
            'password' => 'mypassword',
        ]);

        $this->assertStringStartsWith('$argon2id$', $user->password);
        $this->assertEquals("hash{$this->suffix}", $user->username);
    }

    public function testUpdate(): void
    {
        $user = $this->repository->create([
            'nama_lengkap' => 'Before',
            'username' => "update{$this->suffix}",
            'email' => "update{$this->suffix}@example.com",
            'password' => 'password123',
        ]);

        $updated = $this->repository->update($user->id, ['nama_lengkap' => 'After']);
        $this->assertEquals('After', $updated->namaLengkap);
    }

    public function testUpdatePassword(): void
    {
        $user = $this->repository->create([
            'nama_lengkap' => 'Pw Update',
            'username' => "pw{$this->suffix}",
            'email' => "pw{$this->suffix}@example.com",
            'password' => 'oldpassword',
        ]);

        $oldHash = $user->password;
        $updated = $this->repository->update($user->id, ['password' => 'newpassword']);
        $this->assertNotEquals($oldHash, $updated->password);
        $this->assertStringStartsWith('$argon2id$', $updated->password);
    }

    public function testDelete(): void
    {
        $user = $this->repository->create([
            'nama_lengkap' => 'Del Me',
            'username' => "del{$this->suffix}",
            'email' => "del{$this->suffix}@example.com",
            'password' => 'password123',
        ]);

        $result = $this->repository->delete($user->id);
        $this->assertTrue($result);
        $this->assertNull($this->repository->findById($user->id));
    }

    public function testExistsByUsernameFound(): void
    {
        $this->assertTrue($this->repository->existsByUsername('admin'));
    }

    public function testExistsByUsernameNotFound(): void
    {
        $this->assertFalse($this->repository->existsByUsername('nonexistent_user'));
    }

    public function testExistsByUsernameWithExcludeId(): void
    {
        $this->assertFalse($this->repository->existsByUsername('admin', 1));
    }

    public function testExistsByEmailFound(): void
    {
        $this->assertTrue($this->repository->existsByEmail('admin@imc.local'));
    }

    public function testExistsByEmailNotFound(): void
    {
        $this->assertFalse($this->repository->existsByEmail('nonexistent@example.com'));
    }

    public function testExistsByEmailWithExcludeId(): void
    {
        $this->assertFalse($this->repository->existsByEmail('admin@imc.local', 1));
    }
}
