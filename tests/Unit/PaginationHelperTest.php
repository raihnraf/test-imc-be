<?php

declare(strict_types=1);

namespace Imc\Tests\Unit;

use Imc\Application\Helpers\PaginationHelper;
use Illuminate\Database\Query\Builder;
use PHPUnit\Framework\TestCase;

class PaginationHelperTest extends TestCase
{
    public function testPaginateReturnsCorrectStructure(): void
    {
        $mock = $this->createMockBuilder();
        $result = PaginationHelper::paginate($mock->query, 1, 10);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('page', $result['meta']);
        $this->assertArrayHasKey('per_page', $result['meta']);
        $this->assertArrayHasKey('total', $result['meta']);
        $this->assertArrayHasKey('total_pages', $result['meta']);
    }

    public function testPaginateWithDefaultParams(): void
    {
        $mock = $this->createMockBuilder();
        $result = PaginationHelper::paginate($mock->query);

        $this->assertEquals(1, $result['meta']['page']);
        $this->assertEquals(15, $result['meta']['per_page']);
    }

    public function testPaginateWithCustomParams(): void
    {
        $mock = $this->createMockBuilder(47);
        $result = PaginationHelper::paginate($mock->query, 2, 15);

        $this->assertEquals(2, $result['meta']['page']);
        $this->assertEquals(15, $result['meta']['per_page']);
        $this->assertEquals(47, $result['meta']['total']);
    }

    public function testPaginateClampsPageToMinimumOne(): void
    {
        $mock = $this->createMockBuilder();
        $result = PaginationHelper::paginate($mock->query, 0, 15);

        $this->assertEquals(1, $result['meta']['page']);
    }

    public function testPaginateClampsPerPageToMaximum100(): void
    {
        $mock = $this->createMockBuilder();
        $result = PaginationHelper::paginate($mock->query, 1, 200);

        $this->assertEquals(100, $result['meta']['per_page']);
    }

    public function testPaginateWithEmptyResult(): void
    {
        $mock = $this->createMockBuilder(0);
        $result = PaginationHelper::paginate($mock->query, 1, 10);

        $this->assertEquals(0, $result['meta']['total']);
        $this->assertEquals(0, $result['meta']['total_pages']);
        $this->assertCount(0, $result['data']);
    }

    public function testPaginateCalculatesTotalPagesCorrectly(): void
    {
        $mock = $this->createMockBuilder(47);
        $result = PaginationHelper::paginate($mock->query, 1, 15);

        // ceil(47/15) = 4
        $this->assertEquals(4, $result['meta']['total_pages']);
    }

    private function createMockBuilder(int $total = 10): object
    {
        $mock = $this->createMock(Builder::class);

        $mock->method('count')->willReturn($total);
        $mock->method('forPage')->willReturnSelf();
        $mock->method('get')->willReturn(array_fill(0, min($total, 15), (object) ['id' => 1]));

        return (object) ['query' => $mock];
    }
}
