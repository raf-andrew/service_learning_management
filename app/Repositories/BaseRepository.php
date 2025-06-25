<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\Services\CacheableTrait;
use App\Traits\Services\AuditableTrait;
use App\Contracts\Repositories\RepositoryInterface;

/**
 * Base Repository
 * 
 * Provides a foundation for all repositories with common database operations,
 * caching strategies, and query optimization.
 * 
 * Features:
 * - CRUD operations with caching
 * - Query optimization and eager loading
 * - Transaction support
 * - Soft delete handling
 * - Bulk operations
 * - Search and filtering
 * - Pagination support
 * 
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class BaseRepository implements RepositoryInterface
{
    use CacheableTrait, AuditableTrait;

    /**
     * The model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected Model $model;

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    protected int $cacheTtl = 300;

    /**
     * Cache tags for this repository.
     *
     * @var array<string>
     */
    protected array $cacheTags = [];

    /**
     * @var string
     */
    protected string $modelClass;

    /**
     * @var array
     */
    protected array $defaultRelations = [];

    /**
     * @var array
     */
    protected array $searchableFields = [];

    /**
     * @var array
     */
    protected array $filterableFields = [];

    /**
     * @var array
     */
    protected array $sortableFields = [];

    /**
     * @var int
     */
    protected int $defaultPerPage = 15;

    /**
     * Create a new repository instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function __construct(Model $model = null)
    {
        $this->model = $model ?? $this->createModel();
        $this->modelClass = get_class($this->model);
        $this->cacheTags = [$this->getCacheTag()];
        $this->initializeRepository();
    }

    /**
     * Initialize repository
     */
    protected function initializeRepository(): void
    {
        $this->initializeCaching();
        $this->initializeAudit();
        $this->loadRepositoryConfiguration();
    }

    /**
     * Load repository configuration
     */
    protected function loadRepositoryConfiguration(): void
    {
        $configKey = "repositories.{$this->getRepositoryName()}";
        $config = config($configKey, []);

        $this->defaultRelations = $config['default_relations'] ?? [];
        $this->searchableFields = $config['searchable_fields'] ?? [];
        $this->filterableFields = $config['filterable_fields'] ?? [];
        $this->sortableFields = $config['sortable_fields'] ?? [];
        $this->defaultPerPage = $config['default_per_page'] ?? 15;
    }

    /**
     * Create model instance
     */
    protected function createModel(): Model
    {
        $modelClass = $this->getModelClass();
        return new $modelClass();
    }

    /**
     * Get model class
     */
    abstract protected function getModelClass(): string;

    /**
     * Get repository name
     */
    protected function getRepositoryName(): string
    {
        $className = class_basename($this);
        return str_replace('Repository', '', $className);
    }

    /**
     * Get service name for traits
     */
    protected function getServiceName(): string
    {
        return $this->getRepositoryName();
    }

    /**
     * Find a model by its primary key.
     *
     * @param int $id
     * @param array<string> $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find(int $id, array $with = []): ?Model
    {
        $relations = array_merge($this->defaultRelations, $with);
        $cacheKey = $this->getCacheKey("find:{$id}:" . implode(',', $relations));
        
        return $this->remember($cacheKey, function () use ($id, $relations) {
            $query = $this->model->newQuery();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            $model = $query->find($id);
            
            if ($model) {
                $this->logDataAccess($this->modelClass, $id, 'find');
            }
            
            return $model;
        }, $this->cacheTtl, ["model:{$this->modelClass}", "id:{$id}"]);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param int $id
     * @param array<string> $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id, array $with = []): Model
    {
        $model = $this->find($id, $with);
        
        if (!$model) {
            $this->logErrorEvent('find_or_fail', "Model not found: {$this->modelClass} with ID {$id}");
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "No query results for model [{$this->model->getMorphClass()}] {$id}"
            );
        }
        
        return $model;
    }

    /**
     * Get all models.
     *
     * @param array<string> $with Relationships to eager load
     * @param array<string, mixed> $filters Filters to apply
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAll(array $with = [], array $filters = []): Collection
    {
        $relations = array_merge($this->defaultRelations, $with);
        $cacheKey = $this->getCacheKey('findAll:' . md5(serialize([$relations, $filters])));
        
        return $this->remember($cacheKey, function () use ($relations, $filters) {
            $query = $this->model->newQuery();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            $this->applyFilters($query, $filters);
            
            $models = $query->get();
            
            $this->logDataAccess($this->modelClass, 'all', 'all');
            
            return $models;
        }, $this->cacheTtl, ["model:{$this->modelClass}", "collection:all"]);
    }

    /**
     * Get paginated models.
     *
     * @param int $perPage
     * @param array<string> $with Relationships to eager load
     * @param array<string, mixed> $filters Filters to apply
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = null, array $with = [], array $filters = [], array $sort = []): LengthAwarePaginator
    {
        $perPage = $perPage ?? $this->defaultPerPage;
        $relations = array_merge($this->defaultRelations, $with);
        $cacheKey = $this->getCacheKey('paginate:' . md5(serialize([$perPage, $relations, $filters, $sort])));
        
        return $this->remember($cacheKey, function () use ($perPage, $relations, $filters, $sort) {
            $query = $this->model->newQuery();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            $this->applyFilters($query, $filters);
            $this->applySorting($query, $sort);
            
            $paginator = $query->paginate($perPage);
            
            $this->logDataAccess($this->modelClass, 'paginated', 'paginate');
            
            return $paginator;
        }, $this->cacheTtl, ["model:{$this->modelClass}", "collection:paginated"]);
    }

    /**
     * Create a new model.
     *
     * @param array<string, mixed> $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $data): Model
    {
        $model = $this->model->create($data);
        
        $this->logDataModification($this->modelClass, $model->id, 'create', [], $data);
        $this->clearCache();
        
        Log::info("Model created", [
            'model' => get_class($model),
            'id' => $model->id,
            'data' => $this->sanitizeLogData($data),
        ]);
        
        return $model;
    }

    /**
     * Update a model.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $oldData = $model->toArray();
        $model->update($data);
        
        $this->logDataModification($this->modelClass, $model->id, 'update', $oldData, $data);
        $this->clearCache();
        
        Log::info("Model updated", [
            'model' => get_class($model),
            'id' => $model->id,
            'data' => $this->sanitizeLogData($data),
        ]);
        
        return $model->fresh();
    }

    /**
     * Delete a model.
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        $deleted = $model->delete();
        
        if ($deleted) {
            $this->logDataModification($this->modelClass, $model->id, 'delete', $model->toArray(), []);
            $this->clearCache();
            
            Log::info("Model deleted", [
                'model' => get_class($model),
                'id' => $model->id,
            ]);
        }
        
        return $deleted;
    }

    /**
     * Soft delete a model.
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function softDelete(int $id): bool
    {
        $model = $this->findOrFail($id);
        
        if (method_exists($model, 'delete')) {
            $deleted = $model->delete();
            
            if ($deleted) {
                $this->logDataModification($this->modelClass, $model->id, 'soft_delete', $model->toArray(), []);
                $this->clearCache();
                
                Log::info("Model soft deleted", [
                    'model' => get_class($model),
                    'id' => $model->id,
                ]);
            }
            
            return $deleted;
        }
        
        return $this->delete($id);
    }

    /**
     * Restore a soft-deleted model.
     *
     * @param int $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restore(int $id): bool
    {
        $model = $this->model->withTrashed()->findOrFail($id);
        
        if (method_exists($model, 'restore')) {
            $restored = $model->restore();
            
            if ($restored) {
                $this->clearCache();
                
                Log::info("Model restored", [
                    'model' => get_class($model),
                    'id' => $model->id,
                ]);
            }
            
            return $restored;
        }
        
        return false;
    }

    /**
     * Find models by criteria.
     *
     * @param array<string, mixed> $criteria
     * @param array<string> $with Relationships to eager load
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findBy(array $criteria, array $with = []): Collection
    {
        $relations = array_merge($this->defaultRelations, $with);
        $cacheKey = $this->getCacheKey('findBy:' . md5(serialize([$criteria, $relations])));
        
        return $this->remember($cacheKey, function () use ($criteria, $relations) {
            $query = $this->model->newQuery();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            foreach ($criteria as $field => $value) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
            
            $models = $query->get();
            
            $this->logDataAccess($this->modelClass, 'findBy', 'findBy', ['criteria' => $criteria]);
            
            return $models;
        }, $this->cacheTtl, ["model:{$this->modelClass}", "findBy"]);
    }

    /**
     * Find a model by criteria or create it.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $criteria, array $data = []): Model
    {
        $model = $this->model->where($criteria)->first();
        
        if (!$model) {
            $model = $this->create(array_merge($criteria, $data));
        }
        
        return $model;
    }

    /**
     * Update a model by criteria or create it.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $criteria, array $data): Model
    {
        $model = $this->model->updateOrCreate($criteria, $data);
        
        $this->logDataModification($this->modelClass, $model->id, 'update_or_create', [], $data);
        $this->clearCache();
        
        Log::info("Model updated or created", [
            'model' => get_class($model),
            'id' => $model->id,
            'criteria' => $this->sanitizeLogData($criteria),
            'data' => $this->sanitizeLogData($data),
        ]);
        
        return $model;
    }

    /**
     * Count models.
     *
     * @param array<string, mixed> $filters Filters to apply
     * @return int
     */
    public function count(array $filters = []): int
    {
        $cacheKey = $this->getCacheKey('count:' . md5(serialize($filters)));
        
        return $this->remember($cacheKey, function () use ($filters) {
            $query = $this->model->newQuery();
            $this->applyFilters($query, $filters);
            
            $count = $query->count();
            
            $this->logDataAccess($this->modelClass, 'count', 'count', ['filters' => $filters]);
            
            return $count;
        }, $this->cacheTtl, ["model:{$this->modelClass}", "count"]);
    }

    /**
     * Execute a transaction.
     *
     * @param callable $callback
     * @return mixed
     * @throws \Throwable
     */
    public function transaction(callable $callback)
    {
        return DB::transaction($callback);
    }

    /**
     * Clear cache for this repository.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::tags($this->cacheTags)->flush();
    }

    /**
     * Get cache key for this repository.
     *
     * @param string $suffix
     * @return string
     */
    protected function getCacheKey(string $suffix): string
    {
        return $this->getCacheTag() . ':' . $suffix;
    }

    /**
     * Get cache tag for this repository.
     *
     * @return string
     */
    protected function getCacheTag(): string
    {
        return strtolower(class_basename($this->model));
    }

    /**
     * Apply filters to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string, mixed> $filters
     * @return void
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (in_array($field, $this->filterableFields)) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }
    }

    /**
     * Apply sorting to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array<string> $sort
     * @return void
     */
    protected function applySorting(Builder $query, array $sort): void
    {
        foreach ($sort as $field => $direction) {
            if (in_array($field, $this->sortableFields)) {
                $query->orderBy($field, $direction);
            }
        }
    }

    /**
     * Sanitize data for logging.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function sanitizeLogData(array $data): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'api_key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***HIDDEN***';
            }
        }
        
        return $data;
    }

    /**
     * Get repository statistics
     */
    public function getRepositoryStatistics(): array
    {
        return [
            'model_class' => $this->modelClass,
            'repository_name' => $this->getRepositoryName(),
            'default_relations' => $this->defaultRelations,
            'searchable_fields' => $this->searchableFields,
            'filterable_fields' => $this->filterableFields,
            'sortable_fields' => $this->sortableFields,
            'default_per_page' => $this->defaultPerPage,
            'cache_statistics' => $this->getCacheStatistics(),
            'total_count' => $this->count()
        ];
    }
} 