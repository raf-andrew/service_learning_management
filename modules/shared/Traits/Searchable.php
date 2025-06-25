<?php

namespace Modules\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait Searchable
{
    /**
     * Searchable fields for this model
     */
    protected array $searchableFields = [];

    /**
     * Searchable relationships
     */
    protected array $searchableRelationships = [];

    /**
     * Search filters
     */
    protected array $searchFilters = [];

    /**
     * Search a model with query
     */
    public function search(string $query, array $options = []): Builder
    {
        $builder = $this->getSearchBuilder();
        
        if (empty($query)) {
            return $builder;
        }

        $searchFields = $options['fields'] ?? $this->searchableFields;
        $searchRelationships = $options['relationships'] ?? $this->searchableRelationships;
        $filters = $options['filters'] ?? [];

        // Apply text search
        $builder = $this->applyTextSearch($builder, $query, $searchFields);
        
        // Apply relationship search
        $builder = $this->applyRelationshipSearch($builder, $query, $searchRelationships);
        
        // Apply filters
        $builder = $this->applyFilters($builder, $filters);

        return $builder;
    }

    /**
     * Get search builder
     */
    protected function getSearchBuilder(): Builder
    {
        if (method_exists($this, 'newQuery')) {
            return $this->newQuery();
        }

        if (property_exists($this, 'model')) {
            return $this->model->newQuery();
        }

        throw new \Exception('Searchable trait requires a model or newQuery method');
    }

    /**
     * Apply text search to fields
     */
    protected function applyTextSearch(Builder $builder, string $query, array $fields): Builder
    {
        if (empty($fields)) {
            return $builder;
        }

        $searchTerms = $this->parseSearchQuery($query);

        return $builder->where(function (Builder $query) use ($searchTerms, $fields) {
            foreach ($searchTerms as $term) {
                $query->where(function (Builder $subQuery) use ($term, $fields) {
                    foreach ($fields as $field) {
                        $subQuery->orWhere($field, 'LIKE', "%{$term}%");
                    }
                });
            }
        });
    }

    /**
     * Apply search to relationships
     */
    protected function applyRelationshipSearch(Builder $builder, string $query, array $relationships): Builder
    {
        if (empty($relationships)) {
            return $builder;
        }

        $searchTerms = $this->parseSearchQuery($query);

        return $builder->whereHas($relationships, function (Builder $query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->where(function (Builder $subQuery) use ($term) {
                    foreach ($this->searchableFields as $field) {
                        $subQuery->orWhere($field, 'LIKE', "%{$term}%");
                    }
                });
            }
        });
    }

    /**
     * Apply filters to search
     */
    protected function applyFilters(Builder $builder, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (empty($value)) {
                continue;
            }

            if (is_array($value)) {
                $builder->whereIn($field, $value);
            } else {
                $builder->where($field, $value);
            }
        }

        return $builder;
    }

    /**
     * Parse search query into terms
     */
    protected function parseSearchQuery(string $query): array
    {
        $query = trim($query);
        $terms = preg_split('/\s+/', $query);
        
        return array_filter($terms, function ($term) {
            return strlen($term) >= 2;
        });
    }

    /**
     * Set searchable fields
     */
    public function setSearchableFields(array $fields): void
    {
        $this->searchableFields = $fields;
    }

    /**
     * Add searchable field
     */
    public function addSearchableField(string $field): void
    {
        if (!in_array($field, $this->searchableFields)) {
            $this->searchableFields[] = $field;
        }
    }

    /**
     * Set searchable relationships
     */
    public function setSearchableRelationships(array $relationships): void
    {
        $this->searchableRelationships = $relationships;
    }

    /**
     * Add searchable relationship
     */
    public function addSearchableRelationship(string $relationship): void
    {
        if (!in_array($relationship, $this->searchableRelationships)) {
            $this->searchableRelationships[] = $relationship;
        }
    }

    /**
     * Set search filters
     */
    public function setSearchFilters(array $filters): void
    {
        $this->searchFilters = $filters;
    }

    /**
     * Add search filter
     */
    public function addSearchFilter(string $field, mixed $value): void
    {
        $this->searchFilters[$field] = $value;
    }

    /**
     * Get searchable fields
     */
    public function getSearchableFields(): array
    {
        return $this->searchableFields;
    }

    /**
     * Get searchable relationships
     */
    public function getSearchableRelationships(): array
    {
        return $this->searchableRelationships;
    }

    /**
     * Get search filters
     */
    public function getSearchFilters(): array
    {
        return $this->searchFilters;
    }

    /**
     * Advanced search with multiple criteria
     */
    public function advancedSearch(array $criteria): Builder
    {
        $builder = $this->getSearchBuilder();

        foreach ($criteria as $field => $value) {
            if (empty($value)) {
                continue;
            }

            if (is_array($value)) {
                if (isset($value['operator'])) {
                    $builder->where($field, $value['operator'], $value['value']);
                } else {
                    $builder->whereIn($field, $value);
                }
            } else {
                $builder->where($field, $value);
            }
        }

        return $builder;
    }

    /**
     * Full-text search using database full-text capabilities
     */
    public function fullTextSearch(string $query, array $fields = []): Builder
    {
        $builder = $this->getSearchBuilder();
        
        if (empty($fields)) {
            $fields = $this->searchableFields;
        }

        if (empty($fields)) {
            return $builder;
        }

        $searchFields = implode(',', $fields);
        
        return $builder->whereRaw("MATCH({$searchFields}) AGAINST(? IN BOOLEAN MODE)", [$query]);
    }

    /**
     * Fuzzy search with similarity matching
     */
    public function fuzzySearch(string $query, array $fields = [], float $threshold = 0.7): Builder
    {
        $builder = $this->getSearchBuilder();
        
        if (empty($fields)) {
            $fields = $this->searchableFields;
        }

        if (empty($fields)) {
            return $builder;
        }

        $searchTerms = $this->parseSearchQuery($query);

        return $builder->where(function (Builder $query) use ($searchTerms, $fields, $threshold) {
            foreach ($searchTerms as $term) {
                $query->where(function (Builder $subQuery) use ($term, $fields, $threshold) {
                    foreach ($fields as $field) {
                        $subQuery->orWhereRaw("LEVENSHTEIN({$field}, ?) <= ?", [$term, strlen($term) * (1 - $threshold)]);
                    }
                });
            }
        });
    }

    /**
     * Search with ranking/score
     */
    public function searchWithRanking(string $query, array $options = []): Builder
    {
        $builder = $this->search($query, $options);
        
        $searchFields = $options['fields'] ?? $this->searchableFields;
        
        if (!empty($searchFields)) {
            $searchTerms = $this->parseSearchQuery($query);
            $ranking = $this->buildRankingQuery($searchTerms, $searchFields);
            
            $builder->selectRaw("*, {$ranking} as search_rank")
                   ->orderBy('search_rank', 'desc');
        }

        return $builder;
    }

    /**
     * Build ranking query for search results
     */
    protected function buildRankingQuery(array $terms, array $fields): string
    {
        $ranking = [];
        
        foreach ($terms as $term) {
            foreach ($fields as $field) {
                $ranking[] = "CASE WHEN {$field} LIKE '%{$term}%' THEN 1 ELSE 0 END";
            }
        }

        return implode(' + ', $ranking);
    }

    /**
     * Get search suggestions
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        $builder = $this->getSearchBuilder();
        $searchFields = $this->searchableFields;

        if (empty($searchFields)) {
            return [];
        }

        $suggestions = [];
        
        foreach ($searchFields as $field) {
            $results = $builder->select($field)
                             ->where($field, 'LIKE', "%{$query}%")
                             ->distinct()
                             ->limit($limit)
                             ->pluck($field)
                             ->toArray();
            
            $suggestions = array_merge($suggestions, $results);
        }

        return array_unique(array_slice($suggestions, 0, $limit));
    }
} 