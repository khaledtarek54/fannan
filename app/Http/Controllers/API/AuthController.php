<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Users\CheckPhoneNumberRequest;
use App\Http\Requests\Users\CheckVerificationCodeRequest;
use App\Http\Requests\Users\LoginRequest;
use App\Http\Requests\Users\SocialLoginRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdatePasswordRequest;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends BaseController
{
    public function __construct
    (
        protected UserRepository           $userRepository,
        protected ClientRepository         $clientRepository,
    )
    {
    }

    public function register(StoreUserRequest $storeClientRequest): JsonResponse
    {
        $data = $this->userRepository->store($storeClientRequest);
        if (!$data->status)
            return $this->sendResponse($data->user, trans("app.notification_error"));
        return $this->sendResponse($data, trans("app.created"));
    }

    public function login(LoginRequest $loginRequest): Application|Response|JsonResponse|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        $data = $this->userRepository->checkLogin($loginRequest);
        if (!$data->status)
            return $this->sendError([], 400, $data->message);
        return $this->sendResponse($data, trans("app.done"));
    }

    public function socialLogin(SocialLoginRequest $socialLoginRequest): Application|Response|JsonResponse|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        $data = $this->userRepository->socialLogin($socialLoginRequest->all());
        if (!$data->status)
            return $this->sendError([], 400, $data->message);
        return $this->sendResponse($data, trans("app.done"));
    }

    public function sendCodeAgain(CheckPhoneNumberRequest $checkPhoneNumberRequest): JsonResponse
    {
        $data = $this->userRepository->sendCode($checkPhoneNumberRequest);
        if (!$data->status)
            return $this->sendResponse($data->user, trans("app.notification_error"));
        return $this->sendResponse($data->user, trans("app.done"));
    }

    public function checkCode(CheckVerificationCodeRequest $checkVerificationCodeRequest): Application|Response|JsonResponse|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        $data = $this->userRepository->checkVerificationCode($checkVerificationCodeRequest);
        if (!$data->status)
            return $this->sendError([], 400, trans("auth.wrong_code"));
        return $this->sendResponse($data->user, trans("app.done"));
    }

    public function updatePassword(CheckPhoneNumberRequest $checkPhoneNumberRequest, UpdatePasswordRequest $updatePasswordRequest): JsonResponse
    {
        $user = $this->userRepository->updatePassword($checkPhoneNumberRequest->phone, $updatePasswordRequest->password);
        return $this->sendResponse($user, trans("app.done"));
    }

    public function updateToken(Request $request): JsonResponse
    {
        $this->userRepository->updateFcmToken($request->fcm_token);
        return $this->sendResponse([], trans("app.done"));
    }

     public function checkPhoneExists(Request $request): JsonResponse
    {
        $user = $this->userRepository->getUserByPhone($request->phone);
        return $this->sendResponse(['exists' => $user ? true : false], trans("app.done"));
    }

}
