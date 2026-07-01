<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;

class ArtistQueryBuilder implements BaseQueryBuilder
{
    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('id'),
            AllowedFilter::partial('email'),
            AllowedFilter::partial('name'),
            AllowedFilter::partial('phone'),
            AllowedFilter::partial('role'),
            AllowedFilter::callback('special', function (Builder $query, $value) {
                if ($value)
                    $query->orderByDesc(DB::raw('(select avg(stars) from ratings where ratings.artist_id = users.id)'));
            }),
            AllowedFilter::scope('category'),
            AllowedFilter::callback('city', function (Builder $query, $value) {
                if ($value)
                    $query->whereHas('city', function ($query) use ($value) {
                        if (is_array($value))
                            $query->whereIn('id', $value);
                        else
                            $query->where('id', $value);
                    });
            }),
        ];
    }

    public static function getAllowedIncludes(): array
    {
        return [
            'ratings',
            'userCategories',
            'dates',
            'works',
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'name',
            'created_at',
        ];
    }
}
