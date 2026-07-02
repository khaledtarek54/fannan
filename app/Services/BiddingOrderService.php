<?php

namespace App\Services;

use App\Enums\BiddingOrderArtist;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\BiddingOrderArtist as BiddingOrderArtistAlias;
use App\Models\Order;
use App\Notifications\NewBiddingOfferNotification;
use App\Observers\UpdateBiddingOrderStatus;
use App\Services\Concerns\BiddingOrderArtistRepository;
use App\Services\Concerns\OrderCategoryRepository;
use App\Services\Concerns\OrderDateRepository;
use App\Services\Concerns\OrderOfferRepository;
use App\Services\Contracts\BiddingOrderRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use stdClass;

class BiddingOrderService
{

    public function __construct(
        protected readonly BiddingOrderRepositoryInterface $biddingOrderRepository,
        protected readonly OrderDateRepository             $orderDateRepository,
        protected readonly OrderCategoryRepository         $orderCategoryRepository,
        protected readonly BiddingOrderArtistRepository    $biddingOrderArtistRepository,
    )
    {
    }

    public function all(array $params = [], array $columns = ['*'], int $pagination = 25): LengthAwarePaginator
    {
        return $this->biddingOrderRepository->index($params);
    }

    /**
     * @param int $modelId
     * @return Model
     */
    public function show(int $modelId): Model
    {
        return $this->biddingOrderRepository->findById($modelId);
    }

    /**
     * @param array $payload
     * @return Model|null
     */
    public function store(array $payload): ?Model
    {
        $payload['number'] = "B" . $this->biddingOrderRepository->getModelNumber();
        $payload['client_id'] = auth()->id();
        $payload['type'] = OrderType::BIDDING->value;
        $model = $this->biddingOrderRepository->create($payload);
        $this->storeDates($model->id, $payload['dates']);
        $this->storeCategories($model->id, $payload['talents']);
        $model->setStatus(OrderStatus::PENDING->value);
        return $model;
    }

    /**
     * @param int $modelId
     * @param array $dates
     * @return void
     */
    private function storeDates(int $modelId, array $dates): void
    {
        foreach ($dates as $dateObj) {
            $startDate = Carbon::parse($dateObj['start_date']);
            $endDate = Carbon::parse($dateObj['end_date']);
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $payload['order_id'] = $modelId;
                $payload['start_date'] = $currentDate->format('Y-m-d');
                $payload['end_date'] = $currentDate->format('Y-m-d');
                $payload['start_time'] = $dateObj['start_time'];
                $payload['end_time'] = $dateObj['end_time'];
                $payload['is_completed'] = false;
                $this->orderDateRepository->create($payload);
                $currentDate->addDay();
            }
        }
    }

    private function storeCategories(int $modelId, array $talents): void
    {
        foreach ($talents as $talent) {
            $payload['order_id'] = $modelId;
            $payload['subcategory_id'] = $talent['subcategory_id'];
            $payload['has_budget'] = $talent['has_budget'];
            $payload['budget'] = isset($talent['budget']) ? $talent['budget'] : 0;
            $this->orderCategoryRepository->create($payload);
        }
    }


    /**
     * @param array $payload
     * @return stdClass
     */
    public function storeOffer(array $payload): stdClass
    {
        $payload['artist_id'] = auth()->id();
        $data = new stdClass();
        $hasPendingOffer = $this->biddingOrderArtistRepository->checkIfHasPendingOffer($payload['order_id'], $payload['subcategory_id'],  auth()->id());
        if ($hasPendingOffer) {
            $data->message = trans('app.already_has_offer');
            $data->status = false;
            return $data;
        }
        $status = $this->biddingOrderArtistRepository->checkIfAccepted($payload['order_id'], $payload['subcategory_id']);
        if ($status) {
            $data->message = trans('app.order_subcategory_accepted');
            $data->status = false;
            return $data;
        }
        /** @var BiddingOrderArtistAlias $model */
        $model = $this->biddingOrderArtistRepository->create($payload);
//        $model->setStatus(OrderStatus::PENDING->value);
        /** @var Order $order */
        $order = $this->biddingOrderRepository->findById($payload['order_id'], relations: ['client']);
        $client = $order->client;
        $artist = $model->artist;
        try {
            $client->notify(new NewBiddingOfferNotification($order, $artist));
        } catch (\Exception $exception) {
            Log::info("Error in sending notification");
        }
        event(new UpdateBiddingOrderStatus($model));

        $data->model = $model;
        $data->status = true;
        return $data;
    }


    public function artistHomeBiddingOrders(array $payload)
    {
        return $this->biddingOrderRepository->artistHomeBiddingOrders($payload);
    }
}
