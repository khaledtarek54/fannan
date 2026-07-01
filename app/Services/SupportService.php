<?php

namespace App\Services;

use App\Enums\ModelName;
use App\Enums\OrderType;
use App\Enums\SupportType;
use App\Services\Contracts\SupportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class SupportService
{
    public function __construct(protected readonly SupportRepositoryInterface $supportRepository)
    {
    }

    public function all()
    {
        return $this->supportRepository->getAllSupports();
    }

    /**
     * @param array $payload
     * @return Model|null
     */
    public function create(array $payload): ?Model
    {
        if ($payload['type'] == SupportType::DIRECT_ORDER->value) {
            $payload['model_type'] = ModelName::ORDER->value;
            $payload['model_id'] = $payload['order_id'];
        } elseif ($payload['type'] == SupportType::BIDDING_ORDER->value) {
            $payload['model_type'] = ModelName::BIDDING_ORDER_ARTIST->value;
            $payload['model_id'] = $payload['order_id'];
        }
        $payload['user_id'] = auth()->id();
        return $this->supportRepository->create($payload);
    }


}
