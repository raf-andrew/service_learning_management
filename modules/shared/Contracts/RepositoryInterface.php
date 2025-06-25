<?php

namespace Modules\Shared\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    /**
     * Find a model by ID
     */
    public function find(int $id): ?Model;

    /**
     * Find a model by ID or throw exception
     */
    public function findOrFail(int $id): Model;

    /**
     * Find a model by field
     */
    public function findBy(string $field, mixed $value): ?Model;

    /**
     * Get all models
     */
    public function all(): Collection;

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Create a new model
     */
    public function create(array $data): Model;

    /**
     * Update a model
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a model
     */
    public function delete(int $id): bool;

    /**
     * Get models with relationships
     */
    public function with(array $relationships): Builder;

    /**
     * Get models with conditions
     */
    public function where(string $column, string $operator, mixed $value): Builder;

    /**
     * Get models with multiple conditions
     */
    public function whereIn(string $column, array $values): Builder;

    /**
     * Get models ordered by column
     */
    public function orderBy(string $column, string $direction = 'asc'): Builder;

    /**
     * Get models with limit
     */
    public function limit(int $limit): Builder;

    /**
     * Get query builder instance
     */
    public function query(): Builder;

    /**
     * Get model instance
     */
    public function getModel(): Model;

    /**
     * Set model instance
     */
    public function setModel(Model $model): void;

    /**
     * Get repository statistics
     */
    public function getStatistics(): array;

    /**
     * Execute a transaction
     */
    public function transaction(callable $callback): mixed;

    /**
     * Get count of models
     */
    public function count(): int;

    /**
     * Check if model exists
     */
    public function exists(int $id): bool;

    /**
     * Clear repository cache
     */
    public function clearCache(): void;
} 