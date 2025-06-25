<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository Interface
 * 
 * Defines the contract that all repositories must implement.
 * Ensures consistent data access patterns across the application.
 */
interface RepositoryInterface
{
    /**
     * Find model by ID
     */
    public function find($id, array $relations = []): ?Model;

    /**
     * Find model by ID or fail
     */
    public function findOrFail($id, array $relations = []): Model;

    /**
     * Find model by field
     */
    public function findBy(string $field, $value, array $relations = []): ?Model;

    /**
     * Get all models
     */
    public function all(array $relations = [], array $filters = []): Collection;

    /**
     * Get paginated models
     */
    public function paginate(int $perPage = null, array $relations = [], array $filters = [], array $sort = []): LengthAwarePaginator;

    /**
     * Create model
     */
    public function create(array $data): Model;

    /**
     * Update model
     */
    public function update(Model $model, array $data): Model;

    /**
     * Delete model
     */
    public function delete(Model $model): bool;

    /**
     * Search models
     */
    public function search(string $term, array $relations = [], array $filters = [], int $perPage = null): LengthAwarePaginator;

    /**
     * Filter models
     */
    public function filter(array $filters, array $relations = [], array $sort = [], int $perPage = null): LengthAwarePaginator;

    /**
     * Count models
     */
    public function count(array $filters = []): int;

    /**
     * Get repository statistics
     */
    public function getRepositoryStatistics(): array;
} 