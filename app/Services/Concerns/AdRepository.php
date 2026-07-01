<?php

namespace App\Services\Concerns;

use App\Models\Ad;
use App\QueryBuilders\AdQueryBuilder;
use App\Services\Contracts\AdRepositoryInterface;

class AdRepository extends BaseRepository implements AdRepositoryInterface
{

    public function __construct(Ad $ad, AdQueryBuilder $adQueryBuilder)
    {
        $this->setModel($ad)->setQueryBuilder($adQueryBuilder);
    }

}
