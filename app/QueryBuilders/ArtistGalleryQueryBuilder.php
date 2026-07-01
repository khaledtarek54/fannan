<?php

namespace App\QueryBuilders;

use Spatie\QueryBuilder\AllowedFilter;

class ArtistGalleryQueryBuilder implements BaseQueryBuilder
{

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('user_id'),
        ];
    }

    public static function getAllowedIncludes(): array
    {
       return [];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'created_at',
            'id'
        ];
    }
}
