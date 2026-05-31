<?php

declare(strict_types=1);

namespace Imc\Application\Helpers;

use Imc\Domain\Shared\PaginatedResult;

class PaginationHelper
{
    public static function format(array $items, PaginatedResult $paginated): array
    {
        return [
            'data' => $items,
            'meta' => [
                'page' => $paginated->page,
                'per_page' => $paginated->perPage,
                'total' => $paginated->total,
                'total_pages' => $paginated->totalPages(),
            ],
        ];
    }
}
