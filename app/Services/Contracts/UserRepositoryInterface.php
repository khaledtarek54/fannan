<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Model;

interface UserRepositoryInterface extends BaseRepositoryInterface
{

    /**
     * @param string $phone
     * @return Model|null
     */
    public function getUserByPhone(string $phone): Model|null;
}
