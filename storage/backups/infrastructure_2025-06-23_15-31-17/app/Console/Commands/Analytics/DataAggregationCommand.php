<?php

namespace App\Console\Commands\Analytics;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DataAggregationCommand extends Command
{
    protected $signature = 'analytics:aggregate
                          {--type=all : Type of data to aggregate (all|contract|transaction|event|metric)}
                          {--start= : Start date for aggregation (YYYY-MM-DD)}
                          {--end= : End date for aggregation (YYYY-MM-DD)}
                          {--interval=1d : Aggregation interval (1h|1d|1w|1m)}
                          {--metrics= : Comma-separated list of metrics to calculate}
                          {--group-by= : Group by field(s)}
                          {--filter= : Filter expression}
                          {--cache : Cache aggregation results}';

    protected $description = 'Aggregate and calculate metrics from analytics data';

    protected $web3Path;
    protected $analyticsPath;
    protected $aggregationPath;
    protected $dataTypes = ['contract', 'transaction', 'event', 'metric'];
    protected $defaultMetrics = [
        'count',
        'sum',
        'avg',
        'min',
        'max',
        'stddev',
        'percentile_95',
        'unique_count'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->analyticsPath = base_path('storage/analytics');
        $this->aggregationPath = base_path('storage/analytics/aggregations');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create aggregation directory if it doesn't exist
        if (!File::exists($this->aggregationPath)) {
            File::makeDirectory($this->aggregationPath, 0755, true);
        }

        $type = $this->option('type');
        $start = $this->option('start') ? Carbon::parse($this->option('start')) : Carbon::now()->subDays(7);
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : Carbon::now();
        $interval = $this->option('interval');
        $metrics = $this->parseMetrics($this->option('metrics'));
        $groupBy = $this->parseGroupBy($this->option('group-by'));
        $filter = $this->option('filter');
        $cache = $this->option('cache');

        $this->info('Starting analytics data aggregation...');
        $this->info("Period: {$start->format('Y-m-d')} to {$end->format('Y-m-d')}");
        $this->info("Interval: {$interval}");
        $this->info("Metrics: " . implode(', ', $metrics));

        $types = $type === 'all' ? $this->dataTypes : [$type];
        $aggregationResults = [];

        foreach ($types as $dataType) {
            $result = $this->aggregateDataType($dataType, $start, $end, $interval, $metrics, $groupBy, $filter, $cache);
            $aggregationResults[$dataType] = $result;
        }

        // Store aggregation results
        $this->storeAggregationResults($aggregationResults, $start, $end, $interval);

        $this->info('Analytics data aggregation completed');
        return 0;
    }

    protected function parseMetrics($metrics)
    {
        if (empty($metrics)) {
            return $this->defaultMetrics;
        }

        $metricsList = explode(',', $metrics);
        $validMetrics = array_intersect($metricsList, $this->defaultMetrics);

        if (empty($validMetrics)) {
            $this->warn('No valid metrics specified, using defaults');
            return $this->defaultMetrics;
        }

        return $validMetrics;
    }

    protected function parseGroupBy($groupBy)
    {
        if (empty($groupBy)) {
            return ['timestamp'];
        }

        return explode(',', $groupBy);
    }

    protected function aggregateDataType($type, $start, $end, $interval, $metrics, $groupBy, $filter, $cache)
    {
        $this->info("\nAggregating {$type} data...");

        $outputPath = "{$this->aggregationPath}/{$type}";
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Execute aggregation script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/aggregate-{$type}-data.js " .
                  "--start {$start->format('Y-m-d')} " .
                  "--end {$end->format('Y-m-d')} " .
                  "--interval {$interval} " .
                  "--metrics " . implode(',', $metrics) . " " .
                  "--group-by " . implode(',', $groupBy) . " " .
                  "--output {$outputPath}";

        if ($filter) {
            $command .= " --filter \"{$filter}\"";
        }

        if ($cache) {
            $command .= ' --cache';
        }

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            return [
                'success' => false,
                'message' => 'Aggregation failed',
                'details' => $output
            ];
        }

        // Parse aggregation results
        $result = $this->parseAggregationResults($output);

        $this->info("{$type} data aggregation completed");
        return $result;
    }

    protected function parseAggregationResults($output)
    {
        $results = [];
        $currentMetric = null;

        foreach ($output as $line) {
            if (preg_match('/Metric: (.+)/', $line, $matches)) {
                $currentMetric = $matches[1];
                $results[$currentMetric] = [];
            } elseif ($currentMetric && preg_match('/Value: (.+)/', $line, $matches)) {
                $results[$currentMetric][] = $matches[1];
            }
        }

        return [
            'success' => true,
            'results' => $results
        ];
    }

    protected function storeAggregationResults($results, $start, $end, $interval)
    {
        $this->info('Storing aggregation results...');

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $resultPath = "{$this->aggregationPath}/results_{$timestamp}";
        
        if (!File::exists($resultPath)) {
            File::makeDirectory($resultPath, 0755, true);
        }

        // Store results in JSON format
        $data = [
            'timestamp' => $timestamp,
            'period' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'interval' => $interval
            ],
            'results' => $results
        ];

        $jsonPath = "{$resultPath}/aggregation_results.json";
        File::put($jsonPath, json_encode($data, JSON_PRETTY_PRINT));

        $this->info("Aggregation results stored in: {$jsonPath}");
    }
} 