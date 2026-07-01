<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;

class BiddingOrderQueryBuilder implements BaseQueryBuilder
{

    /**
     * @inheritDoc
     */
    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::callback('type', function (Builder $query, $value) {
                if ($value)
                    $query->where('type', $value);
            }),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedIncludes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedSorts(): array
    {
        return [
            'id',
            'created_at'
        ];
    }
}
