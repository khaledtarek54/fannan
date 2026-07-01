<?php

namespace App\QueryBuilders;

class RatingQueryBuilder implements BaseQueryBuilder
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
