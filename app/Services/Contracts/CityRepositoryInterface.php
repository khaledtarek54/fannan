<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CityRepositoryInterface extends BaseRepositoryInterface
{
    public function getCityByName(string $name): ?Model;
}
