<?php

namespace App\Services\Concerns;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\QueryBuilders\BiddingOrderQueryBuilder;
use App\Services\Contracts\BiddingOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BiddingOrderRepository extends BaseRepository implements BiddingOrderRepositoryInterface
{
    public function __construct(Order $order, BiddingOrderQueryBuilder $biddingOrderQueryBuilder)
    {
        $this->setModel($order)->setQueryBuilder($biddingOrderQueryBuilder);
    }

    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations = []): LengthAwarePaginator
    {
        return parent::index(relations: ['offers', 'address']);
    }

    public function artistHomeBiddingOrders(array $payload)
    {
        return $this->model
            ->where('type', OrderType::BIDDING->value)
            ->whereDoesntHave('biddingOrderArtists', function ($query) {
                $query->where('artist_id', auth()->id());
            })
            ->whereHas('address', function ($query) use ($payload) {
                if (isset($payload['city_id'])) {
                    $cityIds = json_decode($payload['city_id']);
                    if (!is_array($cityIds))
                        $cityIds = [$cityIds];
                    $query->whereIn('city_id', $cityIds);
                }
            })
            ->whereDoesntHave('statuses', function ($query) {
                $query->where('name', OrderStatus::ACCEPTED->value);
            })
            ->with(['client', 'statuses', 'biddingOrderArtists'])
            ->orderByDesc('id')
            ->paginate(10);
    }

}
