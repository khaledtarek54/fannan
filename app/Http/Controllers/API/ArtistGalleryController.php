<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Gallery\DeleteGalleryRequest;
use App\Http\Requests\Gallery\StoreArtistGalleryRequest;
use App\Http\Resources\GalleryResource;
use App\Services\ArtistGalleryService;
use Illuminate\Http\JsonResponse;

class ArtistGalleryController extends BaseController
{
    public function __construct(protected readonly ArtistGalleryService $artistGalleryService)
    {
    }

    public function index(): JsonResponse
    {
        $works = $this->artistGalleryService->all();
        return response()->json([
            'status' => true,
            'works' => GalleryResource::collection($works)
        ]);
    }

    public function create(StoreArtistGalleryRequest $storeArtistGalleryRequest): JsonResponse
    {
        $this->artistGalleryService->create($storeArtistGalleryRequest->all());
        return response()->json([
            'status' => true,
            'message' => trans('app.done')
        ]);
    }

    public function update(StoreArtistGalleryRequest $storeArtistGalleryRequest): JsonResponse
    {
        $this->artistGalleryService->update($storeArtistGalleryRequest->gallery_id, $storeArtistGalleryRequest->all());
        return response()->json([
            'status' => true,
            'message' => trans('app.done')
        ]);
    }

    public function destroy(DeleteGalleryRequest $deleteGalleryRequest)
    {
        $this->artistGalleryService->destroy($deleteGalleryRequest->gallery_id);
        return response()->json([
            'status' => true,
            'message' => trans('app.done')
        ]);
    }


}
