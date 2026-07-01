<?php

namespace App\Services\Concerns;

use App\Models\Support;
use App\QueryBuilders\SupportQueryBuilder;
use App\Services\Contracts\SupportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupportRepository extends BaseRepository implements SupportRepositoryInterface
{
    public function __construct(Support $support, SupportQueryBuilder $supportQueryBuilder)
    {
        $this->setModel($support)->setQueryBuilder($supportQueryBuilder);
    }

    public function getAllSupports()
    {
        return $this->model->newQuery()
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('reply_user_id', auth()->id());
            })
            ->with(['user', 'replyUser', 'model'])
            ->get();
    }
}
