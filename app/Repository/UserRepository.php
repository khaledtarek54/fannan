<?php

namespace App\Repository;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\SendOTPNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
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
        $user->verification_code = Controller::createVerificationCode();
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
        $user = User::withTrashed()->where('email', $payload['email'])->first();

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
        if ($user->verification_code == $request->verification_code) {
            $user->is_verified = true;
            $user->save();
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
        // [SECURITY] Verify the SMS code before resetting — previously ANY phone number could
        // reset a password (and got a valid token back). See docs/CODE_REVIEW_FINDINGS.md B3.
        abort_unless($user && (string) $user->verification_code === (string) $code, 403, trans('auth.wrong_code'));
        $user->password = $password;
        $user->save();
        $data = new \stdClass();
        $data->user = new UserResource($user);
        $data->token = $user->createToken('authToken')->accessToken;;
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
