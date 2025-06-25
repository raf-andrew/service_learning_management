<?php

namespace App\Console\Commands\Analytics;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class DataAlertCommand extends Command
{
    protected $signature = 'analytics:alert
                          {action : Action to perform (create|list|update|delete|test)}
                          {--name= : Alert name}
                          {--type= : Alert type (threshold|anomaly|trend)}
                          {--metric= : Metric to monitor}
                          {--condition= : Alert condition (gt|lt|eq|neq)}
                          {--value= : Threshold value}
                          {--window=1h : Time window for evaluation}
                          {--frequency=5m : Check frequency}
                          {--channels= : Notification channels (email|slack|webhook)}
                          {--severity=info : Alert severity (info|warning|critical)}
                          {--description= : Alert description}';

    protected $description = 'Manage analytics data alerts and monitoring';

    protected $web3Path;
    protected $analyticsPath;
    protected $alertsPath;
    protected $alertTypes = ['threshold', 'anomaly', 'trend'];
    protected $severityLevels = ['info', 'warning', 'critical'];
    protected $notificationChannels = ['email', 'slack', 'webhook'];

    public function __construct()
    {
        parent::__construct();
        $this->web3Path = base_path('.web3');
        $this->analyticsPath = base_path('storage/analytics');
        $this->alertsPath = base_path('storage/analytics/alerts');
    }

    public function handle()
    {
        if (!File::exists($this->web3Path)) {
            $this->error('Web3 directory not found');
            return 1;
        }

        // Create alerts directory if it doesn't exist
        if (!File::exists($this->alertsPath)) {
            File::makeDirectory($this->alertsPath, 0755, true);
        }

        $action = $this->argument('action');
        $name = $this->option('name');
        $type = $this->option('type');
        $metric = $this->option('metric');
        $condition = $this->option('condition');
        $value = $this->option('value');
        $window = $this->option('window');
        $frequency = $this->option('frequency');
        $channels = $this->parseChannels($this->option('channels'));
        $severity = $this->option('severity');
        $description = $this->option('description');

        switch ($action) {
            case 'create':
                return $this->createAlert($name, $type, $metric, $condition, $value, $window, $frequency, $channels, $severity, $description);
            case 'list':
                return $this->listAlerts();
            case 'update':
                return $this->updateAlert($name, $type, $metric, $condition, $value, $window, $frequency, $channels, $severity, $description);
            case 'delete':
                return $this->deleteAlert($name);
            case 'test':
                return $this->testAlert($name);
            default:
                $this->error('Invalid action specified');
                return 1;
        }
    }

    protected function createAlert($name, $type, $metric, $condition, $value, $window, $frequency, $channels, $severity, $description)
    {
        if (!$this->validateAlertParameters($name, $type, $metric, $condition, $value, $severity)) {
            return 1;
        }

        $this->info('Creating new alert...');

        $alertConfig = [
            'name' => $name,
            'type' => $type,
            'metric' => $metric,
            'condition' => $condition,
            'value' => $value,
            'window' => $window,
            'frequency' => $frequency,
            'channels' => $channels,
            'severity' => $severity,
            'description' => $description,
            'created_at' => Carbon::now()->toIso8601String(),
            'status' => 'active'
        ];

        $configPath = "{$this->alertsPath}/{$name}.json";
        File::put($configPath, json_encode($alertConfig, JSON_PRETTY_PRINT));

        // Execute alert creation script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/create-alert.js " .
                  "--config {$configPath}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to create alert');
            return 1;
        }

        $this->info('Alert created successfully');
        return 0;
    }

    protected function listAlerts()
    {
        $this->info('Listing active alerts...');

        $alerts = [];
        foreach (File::files($this->alertsPath) as $file) {
            if ($file->getExtension() === 'json') {
                $alert = json_decode(File::get($file->getPathname()), true);
                $alerts[] = $alert;
            }
        }

        if (empty($alerts)) {
            $this->info('No alerts found');
            return 0;
        }

        $this->table(
            ['Name', 'Type', 'Metric', 'Condition', 'Value', 'Status', 'Severity'],
            array_map(function($alert) {
                return [
                    $alert['name'],
                    $alert['type'],
                    $alert['metric'],
                    $alert['condition'],
                    $alert['value'],
                    $alert['status'],
                    $alert['severity']
                ];
            }, $alerts)
        );

        return 0;
    }

    protected function updateAlert($name, $type, $metric, $condition, $value, $window, $frequency, $channels, $severity, $description)
    {
        $configPath = "{$this->alertsPath}/{$name}.json";
        if (!File::exists($configPath)) {
            $this->error("Alert '{$name}' not found");
            return 1;
        }

        $this->info("Updating alert '{$name}'...");

        $alertConfig = json_decode(File::get($configPath), true);
        
        // Update only provided parameters
        if ($type) $alertConfig['type'] = $type;
        if ($metric) $alertConfig['metric'] = $metric;
        if ($condition) $alertConfig['condition'] = $condition;
        if ($value) $alertConfig['value'] = $value;
        if ($window) $alertConfig['window'] = $window;
        if ($frequency) $alertConfig['frequency'] = $frequency;
        if ($channels) $alertConfig['channels'] = $channels;
        if ($severity) $alertConfig['severity'] = $severity;
        if ($description) $alertConfig['description'] = $description;

        $alertConfig['updated_at'] = Carbon::now()->toIso8601String();

        File::put($configPath, json_encode($alertConfig, JSON_PRETTY_PRINT));

        // Execute alert update script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/update-alert.js " .
                  "--config {$configPath}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to update alert');
            return 1;
        }

        $this->info('Alert updated successfully');
        return 0;
    }

    protected function deleteAlert($name)
    {
        $configPath = "{$this->alertsPath}/{$name}.json";
        if (!File::exists($configPath)) {
            $this->error("Alert '{$name}' not found");
            return 1;
        }

        $this->info("Deleting alert '{$name}'...");

        // Execute alert deletion script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/delete-alert.js " .
                  "--name {$name}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Failed to delete alert');
            return 1;
        }

        File::delete($configPath);
        $this->info('Alert deleted successfully');
        return 0;
    }

    protected function testAlert($name)
    {
        $configPath = "{$this->alertsPath}/{$name}.json";
        if (!File::exists($configPath)) {
            $this->error("Alert '{$name}' not found");
            return 1;
        }

        $this->info("Testing alert '{$name}'...");

        // Execute alert test script
        $command = "cd {$this->web3Path} && npx hardhat run scripts/test-alert.js " .
                  "--config {$configPath}";

        $this->info("Executing: {$command}");
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Alert test failed');
            return 1;
        }

        $this->info('Alert test completed successfully');
        return 0;
    }

    protected function validateAlertParameters($name, $type, $metric, $condition, $value, $severity)
    {
        if (empty($name)) {
            $this->error('Alert name is required');
            return false;
        }

        if (!in_array($type, $this->alertTypes)) {
            $this->error('Invalid alert type');
            return false;
        }

        if (empty($metric)) {
            $this->error('Metric is required');
            return false;
        }

        if (empty($condition)) {
            $this->error('Condition is required');
            return false;
        }

        if (empty($value)) {
            $this->error('Value is required');
            return false;
        }

        if (!in_array($severity, $this->severityLevels)) {
            $this->error('Invalid severity level');
            return false;
        }

        return true;
    }

    protected function parseChannels($channels)
    {
        if (empty($channels)) {
            return ['email'];
        }

        $channelList = explode(',', $channels);
        $validChannels = array_intersect($channelList, $this->notificationChannels);

        if (empty($validChannels)) {
            $this->warn('No valid channels specified, using default (email)');
            return ['email'];
        }

        return $validChannels;
    }
} 