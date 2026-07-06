<?php

namespace App\Services;

use Kreait\Firebase\Contract\Auth;
use Throwable;

class FirebaseAuthService
{
    /**
     * [SECURITY][R2-C1] Verify a Firebase ID token server-side and return its VERIFIED email.
     *
     * The email is read from the cryptographically-verified token issued by Firebase — never from
     * client-supplied input. This is what closes the social-login account-takeover: knowing a
     * victim's email is no longer enough to obtain a bearer token for their account; the caller
     * must present a Firebase ID token that Firebase actually issued for that email.
     *
     * @return string|null the verified email, or null when the token is missing / invalid /
     *                      expired / revoked, or carries no verified email.
     */
    public function verifiedEmail(?string $idToken): ?string
    {
        if (! $idToken) {
            return null;
        }

        try {
            /** @var Auth $auth */
            $auth = app('firebase.auth');
            // checkIfRevoked = true so tokens invalidated server-side are rejected as well.
            $verified = $auth->verifyIdToken($idToken, true);
        } catch (Throwable $e) {
            // Any verification failure (bad signature, expired, revoked, misconfigured
            // credentials, network) must fail closed — never fall through to issuing a token.
            return null;
        }

        $claims = $verified->claims();

        // Only trust an email the provider itself marked verified (Google/Apple sign-in do).
        if (! filter_var($claims->get('email_verified'), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $email = $claims->get('email');

        return is_string($email) && $email !== '' ? $email : null;
    }
}
