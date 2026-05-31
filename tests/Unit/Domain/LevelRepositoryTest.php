<?php

declare(strict_types=1);

namespace Imc\Tests\Unit\Domain;

use Imc\Domain\Level\LevelRepository;
use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Tests\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;

class LevelRepositoryTest extends TestCase
{
    private LevelRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new LevelRepository();
    }

    public function testFindById(): void
    {
        $level = $this->repository->findById(1);

        $this->assertNotNull($level);
        $this->assertNotNull($level->namaLevel);
    }

    public function testFindByIdNotFound(): void
    {
        $level = $this->repository->findById(99999);
        $this->assertNull($level);
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
        $builder = $this->repository->findAll(['search' => 'Super']);
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

    public function testCreate(): void
    {
        $level = $this->repository->create(['nama_level' => 'Unit Test Level']);

        $this->assertNotNull($level->id);
        $this->assertEquals('Unit Test Level', $level->namaLevel);
        $this->assertTrue($level->isActive);
    }

    public function testUpdate(): void
    {
        $level = $this->repository->create(['nama_level' => 'Before Update']);
        $updated = $this->repository->update($level->id, ['nama_level' => 'After Update']);

        $this->assertEquals('After Update', $updated->namaLevel);
    }

    public function testDeleteSuccess(): void
    {
        $level = $this->repository->create(['nama_level' => 'To Delete']);
        $result = $this->repository->delete($level->id);

        $this->assertTrue($result);
    }

    public function testDeleteSetsDeletedAt(): void
    {
        $level = $this->repository->create(['nama_level' => 'Soft Del']);
        $this->repository->delete($level->id);

        $row = Capsule::table('levels')->where('id', $level->id)->first();
        $this->assertNotNull($row->deleted_at);
    }

    public function testSoftDeletedNotInFindAll(): void
    {
        $level = $this->repository->create(['nama_level' => 'Hide Me']);
        $this->repository->delete($level->id);

        $builder = $this->repository->findAll([]);
        $results = $builder->get();

        $ids = $results->pluck('id')->toArray();
        $this->assertNotContains($level->id, $ids);
    }

    public function testSoftDeletedNotInFindById(): void
    {
        $level = $this->repository->create(['nama_level' => 'Gone']);
        $this->repository->delete($level->id);

        $found = $this->repository->findById($level->id);
        $this->assertNull($found);
    }

    public function testDeleteNonExistent(): void
    {
        $result = $this->repository->delete(99999);
        $this->assertFalse($result);
    }
}
