<?php

namespace App\Traits\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Searchable Trait
 * 
 * Provides common search functionality for models and services.
 * Implements advanced search capabilities with filtering, sorting, and pagination.
 * 
 * Features:
 * - Full-text search across multiple fields
 * - Advanced filtering with operators
 * - Sorting with multiple columns
 * - Pagination support
 * - Search result highlighting
 * - Search analytics and metrics
 * - Caching for search results
 * 
 * @trait SearchableTrait
 */
trait SearchableTrait
{
    /**
     * Search fields configuration.
     * Override in implementing class to define searchable fields.
     *
     * @var array<string, array>
     */
    protected array $searchableFields = [];

    /**
     * Filter fields configuration.
     * Override in implementing class to define filterable fields.
     *
     * @var array<string, array>
     */
    protected array $filterableFields = [];

    /**
     * Sort fields configuration.
     * Override in implementing class to define sortable fields.
     *
     * @var array<string, array>
     */
    protected array $sortableFields = [];

    /**
     * Default search options.
     *
     * @var array<string, mixed>
     */
    protected array $defaultSearchOptions = [
        'per_page' => 15,
        'sort_by' => 'created_at',
        'sort_order' => 'desc',
        'highlight' => true,
        'cache' => true,
        'cache_ttl' => 300,
    ];

    /**
     * Perform a search with advanced filtering and sorting.
     *
     * @param array<string, mixed> $criteria Search criteria
     * @param array<string, mixed> $options Search options
     * @return array<string, mixed> Search results with metadata
     */
    public function search(array $criteria = [], array $options = []): array
    {
        try {
            $options = array_merge($this->defaultSearchOptions, $options);
            
            // Check cache first
            if ($options['cache']) {
                $cacheKey = $this->generateSearchCacheKey($criteria, $options);
                $cached = $this->getSearchFromCache($cacheKey);
                if ($cached !== null) {
                    return $cached;
                }
            }

            // Build query
            $query = $this->buildSearchQuery($criteria);
            
            // Apply sorting
            $query = $this->applySorting($query, $options);
            
            // Get results
            $results = $this->executeSearch($query, $options);
            
            // Process results
            $processedResults = $this->processSearchResults($results, $criteria, $options);
            
            // Cache results
            if ($options['cache']) {
                $this->cacheSearchResults($cacheKey, $processedResults, $options['cache_ttl']);
            }

            // Log search analytics
            $this->logSearchAnalytics($criteria, $options, $processedResults);

            return $processedResults;

        } catch (\Exception $e) {
            Log::error('Search error', [
                'error' => $e->getMessage(),
                'criteria' => $criteria,
                'options' => $options,
            ]);

            return [
                'data' => [],
                'total' => 0,
                'per_page' => $options['per_page'] ?? 15,
                'current_page' => 1,
                'last_page' => 1,
                'error' => 'Search failed',
            ];
        }
    }

    /**
     * Build the search query based on criteria.
     *
     * @param array<string, mixed> $criteria
     * @return Builder
     */
    protected function buildSearchQuery(array $criteria): Builder
    {
        $query = $this->getBaseQuery();

        // Apply text search
        if (!empty($criteria['search'])) {
            $query = $this->applyTextSearch($query, $criteria['search']);
        }

        // Apply filters
        if (!empty($criteria['filters'])) {
            $query = $this->applyFilters($query, $criteria['filters']);
        }

        // Apply date range filters
        if (!empty($criteria['date_range'])) {
            $query = $this->applyDateRangeFilter($query, $criteria['date_range']);
        }

        // Apply custom filters
        if (!empty($criteria['custom_filters'])) {
            $query = $this->applyCustomFilters($query, $criteria['custom_filters']);
        }

        return $query;
    }

    /**
     * Apply text search across searchable fields.
     *
     * @param Builder $query
     * @param string $searchTerm
     * @return Builder
     */
    protected function applyTextSearch(Builder $query, string $searchTerm): Builder
    {
        if (empty($this->searchableFields)) {
            return $query;
        }

        $searchTerm = trim($searchTerm);
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($searchTerm) {
            foreach ($this->searchableFields as $field => $config) {
                $weight = $config['weight'] ?? 1;
                $operator = $config['operator'] ?? 'LIKE';
                
                switch ($operator) {
                    case 'LIKE':
                        $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
                        break;
                    case 'EXACT':
                        $q->orWhere($field, '=', $searchTerm);
                        break;
                    case 'STARTS_WITH':
                        $q->orWhere($field, 'LIKE', "{$searchTerm}%");
                        break;
                    case 'ENDS_WITH':
                        $q->orWhere($field, 'LIKE', "%{$searchTerm}");
                        break;
                    case 'FULLTEXT':
                        $q->orWhereRaw("MATCH({$field}) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
                        break;
                }
            }
        });
    }

    /**
     * Apply filters to the query.
     *
     * @param Builder $query
     * @param array<string, mixed> $filters
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            if (!isset($this->filterableFields[$field])) {
                continue;
            }

            $config = $this->filterableFields[$field];
            $operator = $config['operator'] ?? '=';

            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $operator, $value);
            }
        }

        return $query;
    }

    /**
     * Apply date range filter.
     *
     * @param Builder $query
     * @param array<string, string> $dateRange
     * @return Builder
     */
    protected function applyDateRangeFilter(Builder $query, array $dateRange): Builder
    {
        $dateField = $dateRange['field'] ?? 'created_at';
        
        if (!empty($dateRange['from'])) {
            $query->where($dateField, '>=', $dateRange['from']);
        }
        
        if (!empty($dateRange['to'])) {
            $query->where($dateField, '<=', $dateRange['to']);
        }

        return $query;
    }

    /**
     * Apply custom filters.
     *
     * @param Builder $query
     * @param array<string, mixed> $customFilters
     * @return Builder
     */
    protected function applyCustomFilters(Builder $query, array $customFilters): Builder
    {
        foreach ($customFilters as $filter) {
            if (isset($filter['field'], $filter['operator'], $filter['value'])) {
                $query->where($filter['field'], $filter['operator'], $filter['value']);
            }
        }

        return $query;
    }

    /**
     * Apply sorting to the query.
     *
     * @param Builder $query
     * @param array<string, mixed> $options
     * @return Builder
     */
    protected function applySorting(Builder $query, array $options): Builder
    {
        $sortBy = $options['sort_by'] ?? 'created_at';
        $sortOrder = $options['sort_order'] ?? 'desc';

        if (isset($this->sortableFields[$sortBy])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }

    /**
     * Execute the search query.
     *
     * @param Builder $query
     * @param array<string, mixed> $options
     * @return mixed
     */
    protected function executeSearch(Builder $query, array $options)
    {
        $perPage = $options['per_page'] ?? 15;
        
        if ($perPage > 0) {
            return $query->paginate($perPage);
        } else {
            return $query->get();
        }
    }

    /**
     * Process search results.
     *
     * @param mixed $results
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function processSearchResults($results, array $criteria, array $options): array
    {
        if ($results instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $results->items();
            $pagination = [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ];
        } else {
            $data = $results->toArray();
            $pagination = [
                'total' => count($data),
                'per_page' => count($data),
                'current_page' => 1,
                'last_page' => 1,
            ];
        }

        // Apply highlighting if requested
        if ($options['highlight'] && !empty($criteria['search'])) {
            $data = $this->highlightSearchResults($data, $criteria['search']);
        }

        return array_merge($pagination, [
            'data' => $data,
            'search_criteria' => $criteria,
            'search_options' => $options,
        ]);
    }

    /**
     * Highlight search terms in results.
     *
     * @param array $data
     * @param string $searchTerm
     * @return array
     */
    protected function highlightSearchResults(array $data, string $searchTerm): array
    {
        $highlighted = [];
        
        foreach ($data as $item) {
            $highlightedItem = $item;
            
            foreach ($this->searchableFields as $field => $config) {
                if (isset($item[$field]) && is_string($item[$field])) {
                    $highlightedItem[$field] = $this->highlightText($item[$field], $searchTerm);
                }
            }
            
            $highlighted[] = $highlightedItem;
        }
        
        return $highlighted;
    }

    /**
     * Highlight text with search terms.
     *
     * @param string $text
     * @param string $searchTerm
     * @return string
     */
    protected function highlightText(string $text, string $searchTerm): string
    {
        $searchTerms = explode(' ', $searchTerm);
        
        foreach ($searchTerms as $term) {
            $term = preg_quote($term, '/');
            $text = preg_replace("/({$term})/i", '<mark>$1</mark>', $text);
        }
        
        return $text;
    }

    /**
     * Generate cache key for search results.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @return string
     */
    protected function generateSearchCacheKey(array $criteria, array $options): string
    {
        $key = 'search_' . md5(serialize($criteria) . serialize($options));
        return $this->getCachePrefix() . $key;
    }

    /**
     * Get search results from cache.
     *
     * @param string $cacheKey
     * @return array<string, mixed>|null
     */
    protected function getSearchFromCache(string $cacheKey): ?array
    {
        return \Illuminate\Support\Facades\Cache::get($cacheKey);
    }

    /**
     * Cache search results.
     *
     * @param string $cacheKey
     * @param array<string, mixed> $results
     * @param int $ttl
     * @return void
     */
    protected function cacheSearchResults(string $cacheKey, array $results, int $ttl): void
    {
        \Illuminate\Support\Facades\Cache::put($cacheKey, $results, $ttl);
    }

    /**
     * Log search analytics.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @param array<string, mixed> $results
     * @return void
     */
    protected function logSearchAnalytics(array $criteria, array $options, array $results): void
    {
        Log::info('Search performed', [
            'criteria' => $criteria,
            'options' => $options,
            'results_count' => $results['total'] ?? 0,
            'execution_time' => microtime(true) - LARAVEL_START,
        ]);
    }

    /**
     * Get the base query for search.
     * Override in implementing class.
     *
     * @return Builder
     */
    abstract protected function getBaseQuery(): Builder;

    /**
     * Get cache prefix for search results.
     * Override in implementing class.
     *
     * @return string
     */
    protected function getCachePrefix(): string
    {
        return 'search_';
    }

    /**
     * Get search suggestions based on partial input.
     *
     * @param string $input
     * @param int $limit
     * @return array<string>
     */
    public function getSearchSuggestions(string $input, int $limit = 10): array
    {
        if (empty($input) || empty($this->searchableFields)) {
            return [];
        }

        $query = $this->getBaseQuery();
        $suggestions = [];

        foreach (array_keys($this->searchableFields) as $field) {
            $results = $query->where($field, 'LIKE', "%{$input}%")
                           ->distinct()
                           ->pluck($field)
                           ->take($limit)
                           ->toArray();
            
            $suggestions = array_merge($suggestions, $results);
        }

        return array_unique(array_slice($suggestions, 0, $limit));
    }

    /**
     * Get search statistics.
     *
     * @return array<string, mixed>
     */
    public function getSearchStatistics(): array
    {
        $query = $this->getBaseQuery();
        
        return [
            'total_records' => $query->count(),
            'searchable_fields' => array_keys($this->searchableFields),
            'filterable_fields' => array_keys($this->filterableFields),
            'sortable_fields' => array_keys($this->sortableFields),
        ];
    }
} 