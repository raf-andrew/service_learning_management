<?php
namespace App\Models;

use Search\SearchQuery;
use Search\SearchEngine;

class DeveloperCredentialSearch
{
    /**
     * Search DeveloperCredentials using the modular search system.
     * @param array $params
     *   Example: [
     *     'filters' => ['DeveloperCredentialActiveFilter' => true],
     *     'sort' => ['sorter' => 'DeveloperCredentialLastUsedSorter', 'direction' => 'desc']
     *   ]
     * @return array
     */
    public static function search(array $params = []): array
    {
        $query = new SearchQuery(array_merge([
            'model' => DeveloperCredential::class
        ], $params));
        return app(SearchEngine::class)->search($query);
    }
}
