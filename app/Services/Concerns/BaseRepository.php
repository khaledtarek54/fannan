<?php

namespace App\Services\Concerns;

use App\Enums\ModelName;
use App\Enums\UserRole;
use App\QueryBuilders\BaseQueryBuilder;
use App\Services\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;


abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;
    protected BaseQueryBuilder $baseQueryBuilder;


    public function setModel(Model $model): BaseRepository
    {
        $this->model = $model;
        return $this;
    }

    public function setQueryBuilder(BaseQueryBuilder $baseQueryBuilder): BaseRepository
    {
        $this->baseQueryBuilder = $baseQueryBuilder;
        return $this;
    }

    public function index(array $params = [], array $columns = ['*'], int $pagination = 25, array $relations = []): LengthAwarePaginator
    {
        $page = request()->input('page', 1);
        $perPage = request()->input('perPage', $pagination > 0 ? $pagination : 25);
        $query = QueryBuilder::for($this->model->newQuery())
            ->allowedFilters($this->baseQueryBuilder->getAllowedFilters())
            ->allowedIncludes($this->baseQueryBuilder->getAllowedIncludes())
            ->allowedSorts($this->baseQueryBuilder->getAllowedSorts());

        $query->when($this->checkAuthClient(), fn($query) => $query->where('client_id', auth()->user()->id));
        $query->when($this->checkModelType(), fn($query) => $query->where('user_id', auth()->user()->id));
        $query->when($this->checkAuthArtist(), fn($query) => $query->where('artist_id', auth()->user()->id));

        if (isset($params['filter'])) {
            foreach ($params['filter'] as $filter => $value) {
                if ($filter == "special" && $value)
                    $query->orderByDesc(DB::raw('(select avg(stars) from ratings where ratings.artist_id = users.id)'));
                elseif ($filter == "status" && $value)
                    $query->currentStatus($value);
                else
                    $query->where($filter, $value);
            }
        }

        if (isset($params['sort'])) {
            foreach ($params['sort'] as $sort => $value)
                $query->orderBy($sort, $value ?? 'asc');
        } else
            $query->orderByDesc('id');

        $query->with(relations: $relations);
        return $query->select($columns)->paginate($perPage, $columns, 'page', $page);
    }

    private function checkAuthClient(): bool
    {
        return auth()->check() && in_array('client_id', $this->model->getFillable()) && auth()->user()->role == UserRole::CLIENT->value;
    }

    private function checkAuthArtist(): bool
    {
        return auth()->check() && in_array('artist_id', $this->model->getFillable()) && auth()->user()->role == UserRole::ARTIST->value;
    }

    private function checkModelType(): bool
    {
        return auth()->check() && in_array('user_id', $this->model->getFillable()) && get_class($this->model) == ModelName::NOTIFICATION->value;
    }

    public function findById(int $modelId, array $columns = ['*'], array $relations = [], array $appends = []): Model
    {
        return $this->model
            ->select($columns)
            ->with($relations)
            ->findOrFail($modelId)
            ->append($appends);
    }

    public function findTrashedById(int $modelId)
    {
        return $this->model
            ->withTrashed()
            ->findOrFail($modelId);
    }

    public function findOnlyTrashedById(int $modelId)
    {
        return $this->model
            ->onlyTrashed()
            ->findOrFail($modelId);
    }

    public function create(array $payload): ?Model
    {
        $model = $this->model->create($this->filterPayload($payload));
        return $model->fresh();
    }

    public function update(int $modelId, array $payload): bool
    {
        $model = $this->findById($modelId);

        return $model->update($this->filterPayload($payload));
    }

    /**
     * @param int $modelId
     * @param string $status
     * @param string|null $reason
     * @return mixed
     */
    public function updateStatus(int $modelId, string $status, string $reason = null): mixed
    {
        $model = $this->findById($modelId);
        return $model->setStatus($status, $reason);
    }

    public function deleteById(int $modelId): bool
    {
        return $this->findById($modelId)
            ->delete();
    }

    public function restoreById(int $modelId): bool
    {
        return $this->findOnlyTrashedById($modelId)
            ->restore();
    }

    public function permanentlyDeleteById(int $modelId): bool
    {
        return $this->findTrashedById($modelId)
            ->forceDelete();
    }

    public function deleteBySelectedIds($array): bool
    {
        $this->model->query()->whereIn('id', json_decode($array))->delete();
        return true;
    }

    public function deleteAll(): bool
    {
        $this->model->query()->delete();
        return true;
    }

    public function getByIds($array): \Illuminate\Database\Eloquent\Collection|array
    {
        return $this->model->query()->whereIn('id', json_decode($array))->get();
    }

    private function filterPayload(array $payload): array
    {
        $fillable = (new $this->model())->getFillable();
        return array_filter(
            $payload,
            function ($key) use ($fillable) {
                return in_array($key, $fillable);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    public function getModelNumber(): int
    {
        return $this->model::query()->count() + 1;
    }
}
