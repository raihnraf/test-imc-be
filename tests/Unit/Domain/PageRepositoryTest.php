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
        $this->assertNotNull($page->name);
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

    public function testFindPaginatedReturnsPaginatedResult(): void
    {
        $result = $this->repository->findPaginated([], 1, 10);

        $this->assertIsArray($result->items);
        $this->assertGreaterThan(0, $result->total);
        $this->assertEquals(1, $result->page);
        $this->assertEquals(10, $result->perPage);
        $this->assertInstanceOf(\Imc\Domain\Page\Page::class, $result->items[0]);
    }

    public function testFindPaginatedOrderedByDisplayOrder(): void
    {
        $this->repository->create(['name' => 'Last', 'route_path' => "/last-{$this->suffix}", 'display_order' => 999]);
        $this->repository->create(['name' => 'First', 'route_path' => "/first-{$this->suffix}", 'display_order' => 1]);

        $result = $this->repository->findPaginated([], 1, 100);

        $firstOrder = $result->items[0]->displayOrder;
        $lastOrder = $result->items[count($result->items) - 1]->displayOrder;
        $this->assertLessThanOrEqual($lastOrder, $firstOrder);
    }

    public function testFindPaginatedWithSearch(): void
    {
        $result = $this->repository->findPaginated(['search' => 'Dashboard'], 1, 10);

        $this->assertGreaterThan(0, $result->total);
        foreach ($result->items as $page) {
            $this->assertInstanceOf(\Imc\Domain\Page\Page::class, $page);
        }
    }

    public function testFindPaginatedWithIsActive(): void
    {
        $this->repository->create([
            'name' => 'Inactive Page',
            'route_path' => "/inactive-{$this->suffix}",
            'is_active' => false,
        ]);

        $result = $this->repository->findPaginated(['is_active' => '0'], 1, 100);

        $this->assertGreaterThan(0, $result->total);
        foreach ($result->items as $page) {
            $this->assertFalse($page->isActive);
        }
    }

    public function testFindPaginatedClampsPageAndPerPage(): void
    {
        $result = $this->repository->findPaginated([], 0, 200);

        $this->assertEquals(1, $result->page);
        $this->assertEquals(100, $result->perPage);
    }

    public function testCount(): void
    {
        $count = $this->repository->count([]);
        $this->assertGreaterThan(0, $count);
    }

    public function testCreate(): void
    {
        $page = $this->repository->create([
            'name' => 'Unit Test',
            'route_path' => "/unit-{$this->suffix}",
        ]);

        $this->assertNotNull($page->id);
        $this->assertEquals('Unit Test', $page->name);
        $this->assertTrue($page->isActive);
    }

    public function testUpdate(): void
    {
        $page = $this->repository->create([
            'name' => 'Before',
            'route_path' => "/before-{$this->suffix}",
        ]);

        $updated = $this->repository->update($page->id, ['name' => 'After']);
        $this->assertEquals('After', $updated->name);
    }

    public function testDelete(): void
    {
        $page = $this->repository->create([
            'name' => 'Delete Me',
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
            'name' => 'Excluded',
            'route_path' => "/excl-{$this->suffix}",
        ]);

        $this->assertTrue($this->repository->existsByRoute("/excl-{$this->suffix}"));
        $this->assertFalse($this->repository->existsByRoute("/excl-{$this->suffix}", $page->id));
    }
}
