<?php

namespace App\Services\Concerns;

use App\Models\City;
use App\Services\Contracts\CityRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class CityRepository extends BaseRepository implements CityRepositoryInterface
{

    public function __construct(City $city)
    {
        $this->setModel($city);
    }

    public function getCityByName(string $name): ?Model
    {
        return $this->model->firstOrCreate(['name' => $name]);
    }
}
