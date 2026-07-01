<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class AdQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
//            AllowedFilter::scope('status')
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
            'created_at',
        ];
    }
}
