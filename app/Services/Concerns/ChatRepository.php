<?php

namespace App\Services\Concerns;


use App\Models\Chat;
use App\QueryBuilders\ChatQueryBuilder;
use App\Services\Contracts\ChatRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ChatRepository extends BaseRepository implements ChatRepositoryInterface
{

    public function __construct(Chat $chat, ChatQueryBuilder $chatQueryBuilder)
    {
        $this->setModel($chat)->setQueryBuilder($chatQueryBuilder);
    }

    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations = []): LengthAwarePaginator
    {
        $relations[] = 'fromUser';
        $relations[] = 'toUser';
        $relations[] = 'reply';
        return parent::index($params, $columns, $pagination, $relations);
    }

    public function chatMessages(array $payload): Collection
    {
        return $this->model->where(function ($query) use ($payload) {
            $query->where('from_user_id', auth()->id())
                ->where('to_user_id', $payload['to_user_id']);
        })
            ->orWhere(function ($query) use ($payload) {
                $query->where('from_user_id', $payload['to_user_id'])
                    ->where('to_user_id', auth()->id());
            })
            ->orderByDesc('created_at')
            ->get();
    }
}
