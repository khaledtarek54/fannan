<?php

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\UserRepositoryInterface;

class UserService
{
    public function __construct(protected readonly UserRepositoryInterface $userRepository)
    {
    }

    public function deleteAccount(array $payload): bool
    {
        /** @var User $user */
        $user = $this->userRepository->getUserByPhone($payload['phone']);
        $user->reason = $payload['reason'];
        $user->save();
        if ($user) {
            $user->delete();
            return true;
        }
        return false;
    }

}
