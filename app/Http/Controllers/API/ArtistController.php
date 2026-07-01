<?php

namespace App\Http\Controllers\API;

use App\Enums\UserRole;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Artists\ArtistCategoryRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UserIdRequest;
use App\Http\Resources\Artist\HomeArtistResource;
use App\Http\Resources\Artist\ProfileArtistResource;
use App\Http\Resources\UserResource;
use App\Repository\UserRepository;
use App\Services\ArtistService;
use Illuminate\Http\JsonResponse;

class ArtistController extends BaseController
{
    public function __construct(
        protected ArtistService  $artistService
    )
    {
    }

    public function index(): JsonResponse
    {
        $artists = HomeArtistResource::collection($this->artistService->index([]));
        return response()->json([
            'status' => true,
            'artists' => $artists,
        ]);
    }

    public function updateCategories(ArtistCategoryRequest $request): JsonResponse
    {
        $this->artistService->updateCategories($request->categories);

        return $this->sendResponse(true, trans('app.done'));
    }

    public function profile(): JsonResponse
    {
        $data = $this->artistService->profile();
        return $this->sendResponse(new UserResource($data), trans('app.done'));
    }

    public function getArtistById(UserIdRequest $userIdRequest): JsonResponse
    {
        $artist = $this->artistService->findById($userIdRequest->user_id);
        return response()->json([
            'artist' => new ProfileArtistResource($artist),
            'status' => true,
        ]);
    }

    public function webRegister()
    {
        $whatsapp = null;
        return view('front.artist_register', compact('whatsapp'));
    }
}
