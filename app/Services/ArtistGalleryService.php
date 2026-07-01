<?php

namespace App\Services;

use App\Models\ArtistGallery;
use App\Services\Contracts\ArtistGalleryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ArtistGalleryService
{

    public function __construct(protected ArtistGalleryRepositoryInterface $artistGalleryRepository)
    {
    }

    public function all(): LengthAwarePaginator
    {
        return $this->artistGalleryRepository->index();
    }

    /**
     * @param array $payload
     * @return Model|null
     */
    public function create(array $payload): ?Model
    {
        return $this->artistGalleryRepository->create($payload);
    }

    /**
     * @param int $modelId
     * @param array $payload
     * @return bool
     */
    public function update(int $modelId, array $payload): bool
    {
        /** @var ArtistGallery $model */
        $model = $this->artistGalleryRepository->findById($modelId);
        if (Storage::exists($model->video)) {
            Storage::delete($model->video);
        }
        return $this->artistGalleryRepository->update($modelId, $payload);

    }

    /**
     * @param int $modelId
     * @return bool
     */
    public function destroy(int $modelId): bool
    {
        /** @var ArtistGallery $model */
        $model = $this->artistGalleryRepository->findById($modelId);
        if (Storage::exists($model->video)) {
            Storage::delete($model->video);
        }
        return $this->artistGalleryRepository->deleteById($modelId);
    }
}
