<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

class OrderQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('artist_id'),
            AllowedFilter::scope('status'),
            AllowedFilter::callback('type', function (Builder $query, $value) {
                if ($value)
                    $query->where('type', $value);
            }),
        ];
    }

    public static function getAllowedIncludes(): array
    {
        return [
            'artist',
            'categories',
            'dates',
            'address',
            'offers',
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
