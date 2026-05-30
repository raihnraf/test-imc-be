<?php

declare(strict_types=1);

namespace Imc\Application\Helpers;

use Illuminate\Database\Query\Builder;

class PaginationHelper
{
    public static function paginate(Builder $query, int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));

        $total = $query->count();
        $items = (clone $query)->forPage($page, $perPage)->get();
        $totalPages = (int) ceil($total / $perPage);

        return [
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ];
    }
}
