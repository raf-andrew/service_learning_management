<?php
namespace App\Models;

use Search\SearchQuery;
use Search\SearchEngine;

class HealthAlertSearch
{
    /**
     * Search HealthAlerts using the modular search system.
     * @param array $params
     *   Example: [
     *     'filters' => ['HealthAlertLevelFilter' => 'critical'],
     *     'sort' => ['sorter' => 'HealthAlertTriggeredAtSorter', 'direction' => 'desc'],
     *     'group' => ['grouper' => 'HealthAlertTypeGrouper', 'field' => 'type']
     *   ]
     * @return array
     */
    public static function search(array $params = []): array
    {
        $query = new SearchQuery(array_merge([
            'model' => self::class
        ], $params));
        return app(SearchEngine::class)->search($query);
    }
}
