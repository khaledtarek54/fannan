<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class OrderOfferQueryBuilder implements BaseQueryBuilder
{

    /**
     * @inheritDoc
     */
    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('order_id'),
            AllowedFilter::scope('status'),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedIncludes(): array
    {
        return [
            'artist'
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedSorts(): array
    {
        return [
            'id',
            'cost',
            'artist_id',
            'created_at'
        ];
    }
}
