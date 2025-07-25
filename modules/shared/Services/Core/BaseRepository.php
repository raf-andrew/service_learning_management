<?php

namespace App\Modules\Shared\Services\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Traits\ErrorHandlingTrait;
use Modules\Shared\Contracts\RepositoryInterface;

abstract class BaseRepository implements RepositoryInterface
{
    use ErrorHandlingTrait;

    /**
     * The model instance
     */
    protected Model $model;

    /**
     * Cache TTL in seconds
     */
    protected int $cacheTtl = 3600;

    /**
     * Whether caching is enabled
     */
    protected bool $cachingEnabled = true;

    /**
     * Cache key prefix
     */
    protected string $cachePrefix = 'repository';

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->initializeRepository();
    }

    /**
     * Initialize repository-specific configurations
     */
    protected function initializeRepository(): void
    {
        $this->cacheTtl = config('modules.cache.repository_ttl', 3600);
        $this->cachingEnabled = config('modules.cache.enabled', true);
        $this->cachePrefix = $this->getCachePrefix();
    }

    /**
     * Get cache prefix for this repository
     */
    abstract protected function getCachePrefix(): string;

    /**
     * Find a model by ID
     */
    public function find(int $id): ?Model
    {
        return $this->withCache("find.{$id}", function () use ($id) {
            return $this->model->find($id);
        });
    }

    /**
     * Find a model by ID or throw exception
     */
    public function findOrFail(int $id): Model
    {
        return $this->withCache("find_or_fail.{$id}", function () use ($id) {
            return $this->model->findOrFail($id);
        });
    }

    /**
     * Find a model by field
     */
    public function findBy(string $field, mixed $value): ?Model
    {
        return $this->withCache("find_by.{$field}.{$value}", function () use ($field, $value) {
            return $this->model->where($field, $value)->first();
        });
    }

    /**
     * Get all models
     */
    public function all(): Collection
    {
        return $this->withCache('all', function () {
            return $this->model->all();
        });
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $cacheKey = "paginate.{$perPage}." . md5(serialize($columns));
        
        return $this->withCache($cacheKey, function () use ($perPage, $columns) {
            return $this->model->paginate($perPage, $columns);
        });
    }

    /**
     * Create a new model
     */
    public function create(array $data): Model
    {
        $model = $this->model->create($data);
        $this->clearCache();
        return $model;
    }

    /**
     * Update a model
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        $result = $model->update($data);
        $this->clearCache();
        return $result;
    }

    /**
     * Delete a model
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        $result = $model->delete();
        $this->clearCache();
        return $result;
    }

    /**
     * Get models with relationships
     */
    public function with(array $relationships): Builder
    {
        return $this->model->with($relationships);
    }

    /**
     * Get models with conditions
     */
    public function where(string $column, string $operator, mixed $value): Builder
    {
        return $this->model->where($column, $operator, $value);
    }

    /**
     * Get models with multiple conditions
     */
    public function whereIn(string $column, array $values): Builder
    {
        return $this->model->whereIn($column, $values);
    }

    /**
     * Get models ordered by column
     */
    public function orderBy(string $column, string $direction = 'asc'): Builder
    {
        return $this->model->orderBy($column, $direction);
    }

    /**
     * Get models with limit
     */
    public function limit(int $limit): Builder
    {
        return $this->model->limit($limit);
    }

    /**
     * Execute a query with caching
     */
    protected function withCache(string $key, callable $callback): mixed
    {
        if (!$this->cachingEnabled) {
            return $callback();
        }

        $cacheKey = $this->buildCacheKey($key);
        return Cache::remember($cacheKey, $this->cacheTtl, $callback);
    }

    /**
     * Build cache key
     */
    protected function buildCacheKey(string $key): string
    {
        return "{$this->cachePrefix}.{$key}";
    }

    /**
     * Clear repository cache
     */
    public function clearCache(): void
    {
        if (!$this->cachingEnabled) {
            return;
        }

        $pattern = $this->buildCacheKey('*');
        Cache::forget($pattern);
    }

    /**
     * Get query builder instance
     */
    public function query(): Builder
    {
        return $this->model->newQuery();
    }

    /**
     * Get model instance
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set model instance
     */
    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    /**
     * Get repository statistics
     */
    public function getStatistics(): array
    {
        return [
            'model' => get_class($this->model),
            'cache_prefix' => $this->cachePrefix,
            'caching_enabled' => $this->cachingEnabled,
            'cache_ttl' => $this->cacheTtl,
        ];
    }

    /**
     * Execute a transaction
     */
    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Get count of models
     */
    public function count(): int
    {
        return $this->withCache('count', function () {
            return $this->model->count();
        });
    }

    /**
     * Check if model exists
     */
    public function exists(int $id): bool
    {
        return $this->withCache("exists.{$id}", function () use ($id) {
            return $this->model->where('id', $id)->exists();
        });
    }
} 