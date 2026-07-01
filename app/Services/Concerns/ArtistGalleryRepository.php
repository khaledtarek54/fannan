<?php

namespace App\Services\Concerns;

use App\Models\ArtistGallery;
use App\QueryBuilders\ArtistGalleryQueryBuilder;
use App\Services\Contracts\ArtistGalleryRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class ArtistGalleryRepository extends BaseRepository implements ArtistGalleryRepositoryInterface
{

    public function __construct(ArtistGallery $artistGallery, ArtistGalleryQueryBuilder $artistGalleryQueryBuilder)
    {
        $this->setModel($artistGallery)->setQueryBuilder($artistGalleryQueryBuilder);
    }

    public function create(array $payload): ?Model
    {
        $model = parent::create($payload);
        $model->user_id = auth()->id();
        $model->save();
        return $model;
    }
}
