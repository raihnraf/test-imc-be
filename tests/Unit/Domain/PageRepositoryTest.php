<?php

declare(strict_types=1);

namespace Imc\Tests\Unit\Domain;

use Imc\Domain\Page\PageRepository;
use Imc\Domain\Page\PageRepositoryInterface;
use Imc\Tests\TestCase;

class PageRepositoryTest extends TestCase
{
    private PageRepositoryInterface $repository;
    private string $suffix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PageRepository();
        $this->suffix = uniqid('', true);
    }

    public function testFindById(): void
    {
        $page = $this->repository->findById(1);
        $this->assertNotNull($page);
        $this->assertNotNull($page->namaPage);
    }

    public function testFindByRoute(): void
    {
        $page = $this->repository->findByRoute('/dashboard');
        $this->assertNotNull($page);
    }

    public function testFindByRouteNotFound(): void
    {
        $page = $this->repository->findByRoute('/nonexistent');
        $this->assertNull($page);
    }

    public function testFindAll(): void
    {
        $builder = $this->repository->findAll([]);
        $this->assertInstanceOf(\Illuminate\Database\Query\Builder::class, $builder);

        $results = $builder->get();
        $this->assertGreaterThan(0, count($results));
    }

    public function testFindAllOrderedByUrutanTampil(): void
    {
        $this->repository->create(['nama_page' => 'Last', 'route_path' => "/last-{$this->suffix}", 'urutan_tampil' => 999]);
        $this->repository->create(['nama_page' => 'First', 'route_path' => "/first-{$this->suffix}", 'urutan_tampil' => 1]);

        $builder = $this->repository->findAll([]);
        $results = $builder->get();

        $firstOrder = (int) $results->first()->urutan_tampil;
        $lastOrder = (int) $results->last()->urutan_tampil;
        $this->assertLessThanOrEqual($lastOrder, $firstOrder);
    }

    public function testFindAllWithSearch(): void
    {
        $builder = $this->repository->findAll(['search' => 'Dashboard']);
        $results = $builder->get();

        $this->assertGreaterThan(0, count($results));
    }

    public function testFindAllWithIsActive(): void
    {
        $this->repository->create([
            'nama_page' => 'Inactive Page',
            'route_path' => "/inactive-{$this->suffix}",
            'is_active' => false,
        ]);

        $builder = $this->repository->findAll(['is_active' => '0']);
        $results = $builder->get();

        $this->assertGreaterThan(0, count($results));
        foreach ($results as $row) {
            $this->assertFalse((bool) $row->is_active);
        }
    }

    public function testCreate(): void
    {
        $page = $this->repository->create([
            'nama_page' => 'Unit Test',
            'route_path' => "/unit-{$this->suffix}",
        ]);

        $this->assertNotNull($page->id);
        $this->assertEquals('Unit Test', $page->namaPage);
        $this->assertTrue($page->isActive);
    }

    public function testUpdate(): void
    {
        $page = $this->repository->create([
            'nama_page' => 'Before',
            'route_path' => "/before-{$this->suffix}",
        ]);

        $updated = $this->repository->update($page->id, ['nama_page' => 'After']);
        $this->assertEquals('After', $updated->namaPage);
    }

    public function testDelete(): void
    {
        $page = $this->repository->create([
            'nama_page' => 'Delete Me',
            'route_path' => "/delete-{$this->suffix}",
        ]);

        $result = $this->repository->delete($page->id);
        $this->assertTrue($result);
        $this->assertNull($this->repository->findById($page->id));
    }

    public function testExistsByRouteFound(): void
    {
        $this->assertTrue($this->repository->existsByRoute('/dashboard'));
    }

    public function testExistsByRouteNotFound(): void
    {
        $this->assertFalse($this->repository->existsByRoute('/nonexistent-page'));
    }

    public function testExistsByRouteWithExcludeId(): void
    {
        $page = $this->repository->create([
            'nama_page' => 'Excluded',
            'route_path' => "/excl-{$this->suffix}",
        ]);

        $this->assertTrue($this->repository->existsByRoute("/excl-{$this->suffix}"));
        $this->assertFalse($this->repository->existsByRoute("/excl-{$this->suffix}", $page->id));
    }
}
