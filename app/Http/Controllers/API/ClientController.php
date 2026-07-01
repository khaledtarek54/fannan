<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Users\ClientCompleteProfileRequest;
use App\Repository\ClientRepository;
use Illuminate\Http\JsonResponse;

class ClientController extends BaseController
{
    public function __construct(
        protected ClientRepository $clientRepository
    )
    {
    }

    public function completeProfile(ClientCompleteProfileRequest $clientCompleteProfileRequest): JsonResponse
    {
        $data = $this->clientRepository->complete($clientCompleteProfileRequest);
        return $this->sendResponse($data, trans('app.done'));
    }

    public function updateProfile(ClientCompleteProfileRequest $clientCompleteProfileRequest): JsonResponse
    {
        $data = $this->clientRepository->complete($clientCompleteProfileRequest);
        return $this->sendResponse($data, trans('app.done'));
    }


    public function profile(): JsonResponse
    {
        $data = $this->clientRepository->profile();
        return $this->sendResponse($data, trans('app.done'));
    }

    public function deleteAccount(): JsonResponse
    {
        $this->clientRepository->delete();
        return $this->sendResponse(true, trans('app.done'));
    }

}
