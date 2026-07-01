<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class TransactionQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('user_id'),
            AllowedFilter::exact('type'),
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
            'created_at',
            'amount',
        ];
    }
}
