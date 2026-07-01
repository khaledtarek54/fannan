<?php

namespace App\Services\Concerns;

use App\Models\User;
use App\Services\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{

    public function __construct(User $user)
    {
        $this->setModel($user);
    }

    public function getUserByPhone(string $phone): Model|null
    {
        return $this->model->where('phone', $phone)->first();
    }
}
