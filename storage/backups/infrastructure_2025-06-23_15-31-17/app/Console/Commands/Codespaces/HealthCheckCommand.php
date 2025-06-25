<?php

namespace App\Console\Commands\Codespaces;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class HealthCheckCommand extends Command
{
    protected $signature = 'codespaces:health-check';
    protected $description = 'Check the health of Codespaces services';

    protected $logDir;
    protected $failuresDir;
    protected $completeDir;
    protected $timestamp;
    protected $logFile;

    public function handle()
    {
        $this->initializeDirectories();
        $this->checkServices();
        return 0;
    }

    protected function initializeDirectories()
    {
        $this->logDir = base_path('.codespaces/log');
        $this->failuresDir = $this->logDir . '/failures';
        $this->completeDir = $this->logDir . '/complete';
        $this->timestamp = now()->format('Ymd-His');
        $this->logFile = "{$this->logDir}/health-check-{$this->timestamp}.log";

        File::makeDirectory($this->logDir, 0755, true, true);
        File::makeDirectory($this->failuresDir, 0755, true, true);
        File::makeDirectory($this->completeDir, 0755, true, true);
    }

    protected function checkServices()
    {
        $services = [
            'MySQL' => ['host' => 'localhost', 'port' => 3306],
            'Redis' => ['host' => 'localhost', 'port' => 6379],
            'MailHog' => ['host' => 'localhost', 'port' => 1025],
        ];

        $results = [];
        $allHealthy = true;

        foreach ($services as $name => $config) {
            $healthy = $this->checkServiceHealth($name, $config['host'], $config['port']);
            $results[$name] = [
                'status' => $healthy ? 'healthy' : 'unhealthy',
                'port' => $config['port']
            ];
            $allHealthy = $allHealthy && $healthy;
        }

        $this->generateReport($results);
        $this->moveLogFile($allHealthy);

        if (!$allHealthy) {
            $this->error('❌ Health check failed - see ' . $this->failuresDir . '/health-check-' . $this->timestamp . '.log for details');
            return 1;
        }

        $this->info('✅ All services are healthy');
        return 0;
    }

    protected function checkServiceHealth(string $serviceName, string $host, int $port): bool
    {
        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, 5);
            if ($socket) {
                fclose($socket);
                $this->log("✅ {$serviceName} is healthy (Port {$port})");
                return true;
            }
            $this->log("❌ {$serviceName} is not responding (Port {$port})");
            return false;
        } catch (\Exception $e) {
            $this->log("❌ {$serviceName} health check failed: " . $e->getMessage());
            return false;
        }
    }

    protected function log(string $message)
    {
        $logMessage = now()->format('Y-m-d H:i:s') . ': ' . $message;
        File::append($this->logFile, $logMessage . PHP_EOL);
        $this->line($logMessage);
    }

    protected function generateReport(array $results)
    {
        $report = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'services' => $results
        ];

        $reportFile = "{$this->logDir}/health-report-{$this->timestamp}.json";
        File::put($reportFile, json_encode($report, JSON_PRETTY_PRINT));
    }

    protected function moveLogFile(bool $allHealthy)
    {
        $targetDir = $allHealthy ? $this->completeDir : $this->failuresDir;
        $targetFile = "{$targetDir}/health-check-{$this->timestamp}.log";
        File::move($this->logFile, $targetFile);
    }
} 