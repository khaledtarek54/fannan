<?php

namespace App\Services\Concerns;

use App\Enums\OrderStatus;
use App\Models\BiddingOrderArtist;
use App\QueryBuilders\BiddingOrderArtistQueryBuilder;
use App\Services\Contracts\BiddingOrderArtistRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BiddingOrderArtistRepository extends BaseRepository implements BiddingOrderArtistRepositoryInterface
{
    public function __construct(BiddingOrderArtist $biddingOrderArtist, BiddingOrderArtistQueryBuilder $biddingOrderArtistQueryBuilder)
    {
        $this->setModel($biddingOrderArtist)->setQueryBuilder($biddingOrderArtistQueryBuilder);
    }

    public function all(): LengthAwarePaginator
    {
        return parent::index(relations: ['artist', 'subcategory']);
    }

    /**
     * @param int $orderId
     * @param int $subcategoryId
     * @return bool
     */
    public function checkIfAccepted(int $orderId, int $subcategoryId): bool
    {
        /** @var BiddingOrderArtist $model */
        $model = $this->model->where('order_id', $orderId)->where('subcategory_id', $subcategoryId)->where('is_accepted', 1)->first();
        return (bool)$model;
    }

    /**
     * @param int $orderId
     * @param int $subcategoryId
     * @param int $artistId
     * @return bool
     */
    public function checkIfHasPendingOffer(int $orderId, int $subcategoryId, int $artistId): bool
    {
        /** @var BiddingOrderArtist $model */
        $model = $this->model->where('order_id', $orderId)
            ->where('artist_id', $artistId)
            ->where('subcategory_id', $subcategoryId)
            ->where('is_accepted', 0)
            ->first();
        return (bool)$model;
    }


    /**
     * @param int $orderId
     * @param int $subcategoryId
     * @return void
     */
    public function rejectPendingOffers(int $orderId, int $subcategoryId): void
    {
        $records = $this->model->where('order_id', $orderId)->where('subcategory_id', $subcategoryId)->where('is_accepted', 0)->get();
        foreach ($records as $record) {
            $record->setStatus(OrderStatus::REJECTED->value);
        }
    }
}
