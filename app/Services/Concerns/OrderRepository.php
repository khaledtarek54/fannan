<?php

namespace App\Services\Concerns;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderOffer;
use App\QueryBuilders\OrderQueryBuilder;
use App\Services\Contracts\OrderCategoryRepositoryInterface;
use App\Services\Contracts\OrderDateRepositoryInterface;
use App\Services\Contracts\OrderOfferRepositoryInterface;
use App\Services\Contracts\OrderRepositoryInterface;
use App\Services\Contracts\UserCategoryRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStatus\Exceptions\InvalidStatus;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{

    public function __construct(
        Order                                               $order,
        OrderQueryBuilder                                   $orderQueryBuilder,
        protected readonly UserCategoryRepositoryInterface  $userCategoryRepository,
        protected readonly OrderCategoryRepositoryInterface $orderCategoryRepository,
        protected readonly OrderDateRepositoryInterface     $orderDateRepository,
        protected readonly OrderOfferRepositoryInterface    $orderOfferRepository,
    )
    {
        $this->setModel($order)
            ->setQueryBuilder($orderQueryBuilder);
    }


    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations = []): LengthAwarePaginator
    {
        $defaultRelations = ['address.city', 'artist', 'dates', 'categories', 'offers', 'rating', 'biddingOrderArtists', 'acceptedBiddingOrderArtists', 'statuses'];
        $relations = array_merge($relations, $defaultRelations);
        return parent::index($params, $columns, $pagination, $relations);
    }

    /**
     * @param array $payload
     * @return Model|null
     * @throws InvalidStatus
     */
    public function create(array $payload): ?Model
    {
        $payload['number'] = "D" . parent::getModelNumber();
        $payload['client_id'] = auth()->id();
        /** @var Order $model */
        $model = parent::create($payload);
        $this->storeCategories($model, $payload);
        $this->storeOrderDates($model->id, $payload['dates']);
        $model->setStatus(OrderStatus::ARTIST_PENDING->value);
        return $model;
    }

    public function storeCategories(Order $model, array $payload): void
    {
        $subcategories = $this->userCategoryRepository->getUserCategories($payload['artist_id'], $payload['subcategories']);
        foreach ($subcategories as $subcategory) {
            $data['order_id'] = $model->id;
            $data['subcategory_id'] = $subcategory->subcategory_id;
            $data['from_range'] = $subcategory->priceRange?->from;
            $data['to_range'] = $subcategory->priceRange?->to;
            $this->orderCategoryRepository->create($data);
        }
    }

    /**
     * @param int $modelId
     * @param array $dates
     * @return void
     */
    private function storeOrderDates(int $modelId, array $dates): void
    {
        foreach ($dates as $dateObj) {
            $startDate = Carbon::parse($dateObj['start_date']);
            $endDate = Carbon::parse($dateObj['end_date']);
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $data['order_id'] = $modelId;
                $data['start_date'] = $currentDate->format('Y-m-d');
                $data['end_date'] = $currentDate->format('Y-m-d');
                $data['start_time'] = $dateObj['start_time'];
                $data['end_time'] = $dateObj['end_time'];
                $data['is_completed'] = false;
                $this->orderDateRepository->create($data);
                $currentDate->addDay();
            }
        }
    }

    public function counterOffer(array $payload): Order
    {
        /** @var Order $model */
        $model = $this->findById($payload['order_id']);
        $this->orderOfferRepository->create([
            'order_id' => $model->id,
            'artist_id' => $model->artist_id,
            'cost' => $payload['cost'],
            'counter_to' => $model->cost,
        ]);
        $model->setStatus(OrderStatus::ARTIST_PENDING->value);
        return $model;
    }

    private function getTotalCostToOrder(Order $order)
    {
        if ($order->type == OrderType::DIRECT->value) {
            $offer = $order->offers->last();
            return $offer ? $offer->cost : $order->cost;
        }
        return $order->acceptedBiddingOrderArtists->sum('cost');
    }

    /**
     * @param string $code
     * @param float $cost
     * @return float
     */
    public function calculateCouponAmount(Coupon $coupon, float $cost): float
    {
        $discount = 0;
        if ($coupon->type->value == CouponType::FIXED->value) {
            $discount = $coupon->amount;
        }

        if ($coupon->type->value == CouponType::PERCENTAGE->value) {
            $discount = ($cost * $coupon->amount) / 100;
        }
        return (float)$discount;
    }

    /**
     * @return mixed
     */
    public function getCompletedOrders(): mixed
    {
        // [BL5/BL6] Complete BOTH direct and bidding orders (was DIRECT-only, so bidding orders
        // could never complete). Any paid order not already completed is eligible; the date check
        // (is_complete) happens in the caller.
        return $this->model
            ->where('is_paid', true)
            ->whereDoesntHave('statuses', function ($query) {
                $query->where('name', OrderStatus::COMPLETED->value);
            })
            ->with(['client', 'artist', 'dates', 'statuses', 'acceptedBiddingOrderArtists.artist'])
            ->get();
    }

    public function artistOrders()
    {
        return $this->model
            ->where(function ($query) {
                $query->where('artist_id', auth()->id())
                    ->orWhereHas('biddingOrderArtists', function ($query) {
                        $query->where('artist_id', auth()->id())
                            ->where('is_accepted', true);
                    });
            })
            ->whereHas('statuses', function ($query) {
                $query->whereIn('name', [OrderStatus::PENDING->value, OrderStatus::ARTIST_PENDING->value, OrderStatus::ACCEPTED->value]);
            })
            ->with(['address.city', 'categories', 'rating', 'biddingOrderArtists', 'statuses'])
            ->get();
    }
}
