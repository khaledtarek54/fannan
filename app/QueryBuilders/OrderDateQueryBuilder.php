<?php

namespace App\QueryBuilders;

class OrderDateQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [];
    }

    public static function getAllowedIncludes(): array
    {
        return [];
    }

    public static function getAllowedSorts(): array
    {
        return [];
    }
}
