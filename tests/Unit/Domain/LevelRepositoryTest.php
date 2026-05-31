<?php

declare(strict_types=1);

namespace Imc\Tests\Unit\Domain;

use Illuminate\Database\Capsule\Manager as Capsule;
use Imc\Domain\Level\LevelRepository;
use Imc\Domain\Level\LevelRepositoryInterface;
use Imc\Tests\TestCase;

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
        $this->assertNotNull($level->name);
    }

    public function testFindByIdNotFound(): void
    {
        $level = $this->repository->findById(99999);
        $this->assertNull($level);
    }

    public function testFindPaginatedReturnsPaginatedResult(): void
    {
        $result = $this->repository->findPaginated([], 1, 10);

        $this->assertIsArray($result->items);
        $this->assertGreaterThan(0, $result->total);
        $this->assertEquals(1, $result->page);
        $this->assertEquals(10, $result->perPage);
        $this->assertInstanceOf(\Imc\Domain\Level\Level::class, $result->items[0]);
    }

    public function testFindPaginatedWithSearch(): void
    {
        $result = $this->repository->findPaginated(['search' => 'Super'], 1, 10);

        $this->assertGreaterThan(0, $result->total);
        foreach ($result->items as $level) {
            $this->assertInstanceOf(\Imc\Domain\Level\Level::class, $level);
        }
    }

    public function testFindPaginatedWithIsActive(): void
    {
        $result = $this->repository->findPaginated(['is_active' => '1'], 1, 10);

        foreach ($result->items as $level) {
            $this->assertTrue($level->isActive);
        }
    }

    public function testFindPaginatedClampsPageAndPerPage(): void
    {
        $result = $this->repository->findPaginated([], 0, 200);

        $this->assertEquals(1, $result->page);
        $this->assertEquals(100, $result->perPage);
    }

    public function testFindPaginatedEmptyResult(): void
    {
        $result = $this->repository->findPaginated(['search' => 'nonexistent_xyz'], 1, 10);

        $this->assertEquals(0, $result->total);
        $this->assertCount(0, $result->items);
    }

    public function testCount(): void
    {
        $count = $this->repository->count([]);
        $this->assertGreaterThan(0, $count);
    }

    public function testCreate(): void
    {
        $level = $this->repository->create(['name' => 'Unit Test Level']);

        $this->assertNotNull($level->id);
        $this->assertEquals('Unit Test Level', $level->name);
        $this->assertTrue($level->isActive);
    }

    public function testUpdate(): void
    {
        $level = $this->repository->create(['name' => 'Before Update']);
        $updated = $this->repository->update($level->id, ['name' => 'After Update']);

        $this->assertEquals('After Update', $updated->name);
    }

    public function testDeleteSuccess(): void
    {
        $level = $this->repository->create(['name' => 'To Delete']);
        $result = $this->repository->delete($level->id);

        $this->assertTrue($result);
    }

    public function testDeleteSetsDeletedAt(): void
    {
        $level = $this->repository->create(['name' => 'Soft Del']);
        $this->repository->delete($level->id);

        $row = Capsule::table('levels')->where('id', $level->id)->first();
        $this->assertNotNull($row->deleted_at);
    }

    public function testSoftDeletedNotInFindPaginated(): void
    {
        $level = $this->repository->create(['name' => 'Hide Me']);
        $this->repository->delete($level->id);

        $result = $this->repository->findPaginated([], 1, 100);
        $ids = array_map(fn ($l) => $l->id, $result->items);
        $this->assertNotContains($level->id, $ids);
    }

    public function testSoftDeletedNotInFindById(): void
    {
        $level = $this->repository->create(['name' => 'Gone']);
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
