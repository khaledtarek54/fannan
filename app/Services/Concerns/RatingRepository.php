<?php

namespace App\Services\Concerns;

use App\Models\Rating;
use App\QueryBuilders\RatingQueryBuilder;
use App\Services\Contracts\RatingRepositoryInterface;

class RatingRepository extends BaseRepository implements RatingRepositoryInterface
{
    public function __construct(Rating $rate, RatingQueryBuilder $rateQueryBuilder)
    {
        $this->setModel($rate)->setQueryBuilder($rateQueryBuilder);
    }

}
