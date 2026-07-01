<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class BiddingOrderArtistQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('order_id'),
            AllowedFilter::exact('artist_id'),
            AllowedFilter::exact('is_accepted'),
            AllowedFilter::partial('cost'),
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
            'cost',
            'created_at',
        ];
    }
}
