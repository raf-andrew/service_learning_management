<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait QueryOptimizationTrait
{
    /**
     * Apply common filters to a query builder
     */
    protected function applyCommonFilters(Builder $query, Request $request): Builder
    {
        // Apply date range filters
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function (Builder $q) use ($searchTerm) {
                $searchableFields = $this->getSearchableFields();
                foreach ($searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                }
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, $this->getSortableFields())) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }

    /**
     * Get searchable fields for the model
     */
    protected function getSearchableFields(): array
    {
        return ['name', 'description', 'title'];
    }

    /**
     * Get sortable fields for the model
     */
    protected function getSortableFields(): array
    {
        return ['created_at', 'updated_at', 'name', 'id'];
    }

    /**
     * Apply pagination with optimized queries
     */
    protected function paginateWithOptimization(Builder $query, Request $request, int $defaultPerPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = min($request->get('per_page', $defaultPerPage), 100);
        
        return $query->paginate($perPage);
    }

    /**
     * Eager load relationships to prevent N+1 queries
     */
    protected function withRelationships(Builder $query, array $relationships = []): Builder
    {
        $defaultRelationships = $this->getDefaultRelationships();
        $allRelationships = array_merge($defaultRelationships, $relationships);
        
        return $query->with($allRelationships);
    }

    /**
     * Get default relationships to eager load
     */
    protected function getDefaultRelationships(): array
    {
        return [];
    }

    /**
     * Apply caching to query results
     */
    protected function cachedQuery(Builder $query, string $cacheKey, int $ttl = 3600)
    {
        return cache()->remember($cacheKey, $ttl, function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Optimize query by selecting only needed fields
     */
    protected function selectFields(Builder $query, array $fields = []): Builder
    {
        $defaultFields = $this->getDefaultSelectFields();
        $allFields = array_merge($defaultFields, $fields);
        
        return $query->select($allFields);
    }

    /**
     * Get default fields to select
     */
    protected function getDefaultSelectFields(): array
    {
        return ['*'];
    }
} 