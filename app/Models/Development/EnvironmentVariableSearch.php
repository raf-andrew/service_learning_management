<?php
namespace App\Models;

use Search\SearchQuery;
use Search\SearchEngine;

class EnvironmentVariableSearch
{
    /**
     * Search EnvironmentVariables using the modular search system.
     * @param array $params
     *   Example: [
     *     'filters' => ['EnvironmentVariableGroupFilter' => 'production'],
     *     'sort' => ['sorter' => 'EnvironmentVariableKeySorter', 'direction' => 'asc']
     *   ]
     * @return array
     */
    public static function search(array $params = []): array
    {
        $query = new SearchQuery(array_merge([
            'model' => EnvironmentVariable::class
        ], $params));
        return app(SearchEngine::class)->search($query);
    }
}
