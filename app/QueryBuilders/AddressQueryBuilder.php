<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class AddressQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('user_id'),
            AllowedFilter::exact('city_id'),
            AllowedFilter::partial('name'),
            AllowedFilter::partial('description'),
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
