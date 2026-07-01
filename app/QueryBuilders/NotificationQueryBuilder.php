<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class NotificationQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('is_read'),
            AllowedFilter::exact('title'),
            AllowedFilter::exact('body'),
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
            'id', 'created_at', 'is_read'
        ];
    }
}
