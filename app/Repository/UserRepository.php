<?php

namespace App\Repository;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\SendOTPNotification;
use App\Services\FirebaseAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function __construct(private readonly FirebaseAuthService $firebaseAuth)
    {
    }

    public static function getUserByPhone($phone)
    {
        return User::where('phone', $phone)->first();
    }

    public function storeUser($name, $phone_prefix, $phone, $role, $fcm_token, $lang, $password): User
    {
        $user = new User();
        $user->name = $name;
//        $user->phone_prefix = $phone_prefix;
        $user->phone = $phone;
        $user->role = $role;
        $user->fcm_token = $fcm_token ?? "";
        $user->lang = $lang ?? "";
        // [SECURITY][R2-C5] Issue the OTP with a TTL + attempt counter (no static 1234 backdoor).
        $user->freshVerificationCode();
        $user->password = $password;
        $user->save();
        return $user;
    }

    public function store($request): \stdClass
    {
        $data = new \stdClass();
        $user = $this->storeUser($request->name, $request->phone_prefix, $request->phone, $request->role, $request->fcm_token, $request->header('lang'), $request->password);
        $data->user = new UserResource($user);
        $data->status = true;
        $data->token = $user->createToken('authToken')->accessToken;
        return $data;
    }

    public function checkLogin($request): \stdClass
    {
        $data = new \stdClass();
        $user = User::withTrashed()->where('phone', $request->phone)->first();

        // Check if user account blocked(deleted)
        if ($user->deleted_at) {
            $data->status = false;
            $data->message = trans('auth.block_account');
            return $data;
        }
        // Check if password not correct
        $status = Hash::check($request->password, $user->password);
        if (!$status) {
            $data->status = false;
            $data->message = trans('auth.password_wrong');
            return $data;
        }
        if ($request->fcm_token) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
        }
        $data->user = new UserResource($user);
        $data->token = $user->createToken('authToken')->accessToken;
        $data->status = true;
        return $data;
    }

    public function socialLogin(array $payload)
    {
        $data = new \stdClass();

        // [SECURITY][R2-C1] Resolve the account from the Firebase-VERIFIED email, never from a
        // client-supplied email. Previously this trusted `payload['email']` and handed back a
        // Passport token for whatever account was named — a full authentication bypass.
        $email = $this->firebaseAuth->verifiedEmail($payload['id_token'] ?? null);

        if (!$email) {
            $data->status = false;
            $data->message = trans('auth.invalid_social_token');
            return $data;
        }

        $user = User::withTrashed()->where('email', $email)->first();

        // Login-only: a valid token whose email has no account is rejected (no auto-registration).
        if (!$user) {
            $data->status = false;
            $data->message = trans('auth.email_not_registered');
            return $data;
        }

        if (!$user->is_verified) {
            $data->status = false;
            $data->message = trans('auth.not_verified');
            return $data;
        }

        if ($user->deleted_at != null) {
            $data->status = false;
            $data->message = trans('auth.block_account');
            return $data;
        }

        $data->user = new UserResource($user);
        $data->token = $user->createToken('authToken')->accessToken;
        $data->status = true;
        return $data;
    }

    public function sendCode($request): \stdClass
    {
        $data = new \stdClass();

        $user = User::where('phone', $request->phone)->first();
        // [SECURITY][R2-C5] A resend issues a NEW code with a fresh TTL and reset attempt counter,
        // so an expired or locked-out code can be recovered without weakening the per-code ceiling.
        $user->freshVerificationCode();
        $user->save();
        $data->user = new UserResource($user);
        $data->status = true;
//        try {
//            // Send otp via firebase notification
//            $user->notify(new SendOTPNotification($user->verification_code));
//        } catch (\Exception $exception) {
//            $data->status = false;
//        }
        return $data;
    }

    public function checkVerificationCode($request)
    {
        $data = new \stdClass();
        $user = $this->getUserByPhone($request->phone);
        // [SECURITY][R2-C5] Enforce TTL + per-account attempt lockout (was a loose `==` on a
        // never-expiring plaintext code) and consume the code on success (single-use).
        if ($user && $user->verifyCode($request->verification_code)) {
            $user->is_verified = true;
            $user->clearVerificationCode(); // saves is_verified + nulls the code
            $data->status = true;
            $data->user = new UserResource($user);
            return $data;
        }
        $data->status = false;
        return $data;
    }

    public function updatePassword($phone, $password, $code = null)
    {
        $user = $this->getUserByPhone($phone);
        // [SECURITY][R2-C5] Verify the OTP (TTL + attempt lockout) before resetting — previously any
        // phone could reset (B3); a wrong/expired/exhausted code is now rejected. [R2-M6] On success
        // the code is consumed and ALL existing Passport tokens are revoked, so a token obtained via
        // a prior compromise or auth bypass stops working the moment the password changes.
        abort_unless($user && $user->verifyCode($code), 403, trans('auth.wrong_code'));
        $user->password = $password;
        $user->save();
        $user->clearVerificationCode();
        $user->tokens()->delete();
        $data = new \stdClass();
        $data->user = new UserResource($user);
        $data->token = $user->createToken('authToken')->accessToken;
        return $data;
    }

    public function updateFcmToken($fcm_token): bool
    {
        $user = Auth::user();
        $user->update([
            'fcm_token' => $fcm_token
        ]);
        return true;
    }

}
