<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;

final class Pagination
{
    private const DEFAULT_PER_PAGE = 50;

    private const MAX_PER_PAGE = 100;

    public static function simple(EloquentBuilder|QueryBuilder $query, ?Request $request = null): Paginator
    {
        $perPage = $request?->has('per_page') === true
            ? $request->integer('per_page')
            : self::DEFAULT_PER_PAGE;
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        return $query->simplePaginate($perPage);
    }
}
