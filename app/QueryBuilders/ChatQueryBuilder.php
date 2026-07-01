<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class ChatQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('from_user_id'),
            AllowedFilter::exact('to_user_id'),
            AllowedFilter::exact('is_read'),
        ];
    }

    public static function getAllowedIncludes(): array
    {
        return [
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'id',
            'created_at'
        ];
    }
}
