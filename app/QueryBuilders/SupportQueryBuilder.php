<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class SupportQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('model_id'),
            AllowedFilter::exact('model_type'),
            AllowedFilter::exact('user_id'),
        ];
    }

    public static function getAllowedIncludes(): array
    {
        return [];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'created_at',
            'id',
        ];
    }
}
