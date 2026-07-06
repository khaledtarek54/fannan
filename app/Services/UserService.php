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

        // [SECURITY] Verify the SMS verification code before deleting — previously ANY phone number
        // could delete the matching account. See docs/SECURITY_ISSUES.md M7. [R2-C5] Now enforces
        // the code's TTL + per-account attempt lockout instead of a loose string compare.
        abort_unless(
            $user && $user->verifyCode($payload['verification_code'] ?? ''),
            403,
            trans('auth.wrong_code')
        );

        $user->reason = $payload['reason'] ?? null;
        $user->verification_code = null; // single-use
        $user->verification_code_expires_at = null;
        $user->verification_code_attempts = 0;
        $user->save();
        $user->delete();

        return true;
    }

}
