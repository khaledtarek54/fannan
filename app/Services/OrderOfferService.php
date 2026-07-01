<?php

namespace App\Services;

use App\Services\Contracts\OrderOfferRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderOfferService
{
    public function __construct(protected readonly OrderOfferRepositoryInterface $orderOfferRepository)
    {
    }

    public function all(): LengthAwarePaginator
    {
        return $this->orderOfferRepository->index();
    }

    public function updateStatus(int $modelId, string $status)
    {
        $model = $this->orderOfferRepository->findById($modelId);
        $model->setStatus($status);
        return true;
    }
}
