<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Model;

interface BaseQueryBuilder
{
    /**
     * Returns an array of allowed filters.
     *
     * @return array
     */
    public static function getAllowedFilters(): array;

    /**
     * Returns an array of allowed includes.
     *
     * @return array
     */
    public static function getAllowedIncludes(): array;

    /**
     * Returns an array of allowed sorts.
     *
     * @return array
     */
    public static function getAllowedSorts(): array;
}
