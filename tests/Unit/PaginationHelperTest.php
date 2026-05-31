<?php

declare(strict_types=1);

namespace Imc\Tests\Unit;

use Imc\Application\Helpers\PaginationHelper;
use Imc\Domain\Shared\PaginatedResult;
use PHPUnit\Framework\TestCase;

class PaginationHelperTest extends TestCase
{
    public function testFormatReturnsCorrectStructure(): void
    {
        $paginated = new PaginatedResult([['id' => 1]], 1, 10, 1);
        $result = PaginationHelper::format([['id' => 1]], $paginated);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('page', $result['meta']);
        $this->assertArrayHasKey('per_page', $result['meta']);
        $this->assertArrayHasKey('total', $result['meta']);
        $this->assertArrayHasKey('total_pages', $result['meta']);
    }

    public function testFormatWithCustomParams(): void
    {
        $paginated = new PaginatedResult(array_fill(0, 10, 'item'), 2, 15, 47);
        $result = PaginationHelper::format(array_fill(0, 10, 'item'), $paginated);

        $this->assertEquals(2, $result['meta']['page']);
        $this->assertEquals(15, $result['meta']['per_page']);
        $this->assertEquals(47, $result['meta']['total']);
    }

    public function testFormatWithEmptyResult(): void
    {
        $paginated = new PaginatedResult([], 1, 10, 0);
        $result = PaginationHelper::format([], $paginated);

        $this->assertEquals(0, $result['meta']['total']);
        $this->assertEquals(0, $result['meta']['total_pages']);
        $this->assertCount(0, $result['data']);
    }

    public function testFormatCalculatesTotalPagesCorrectly(): void
    {
        $paginated = new PaginatedResult(array_fill(0, 10, 'item'), 1, 15, 47);
        $result = PaginationHelper::format(array_fill(0, 10, 'item'), $paginated);

        $this->assertEquals(4, $result['meta']['total_pages']);
    }

    public function testFormatDelegatesMetadataToPaginatedResult(): void
    {
        $paginated = new PaginatedResult(['item'], 1, 15, 10);
        $result = PaginationHelper::format(['item'], $paginated);

        $this->assertEquals($paginated->page, $result['meta']['page']);
        $this->assertEquals($paginated->perPage, $result['meta']['per_page']);
        $this->assertEquals($paginated->total, $result['meta']['total']);
        $this->assertEquals($paginated->totalPages(), $result['meta']['total_pages']);
    }
}
