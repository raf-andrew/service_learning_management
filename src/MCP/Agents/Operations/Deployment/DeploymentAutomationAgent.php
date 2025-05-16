<?php

namespace App\MCP\Agents\Operations\Deployment;

use App\MCP\Agents\Development\CodeAnalysis\BaseCodeAnalysisAgent;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Deployment Automation Agent
 * 
 * This agent is responsible for:
 * - Automating deployment processes
 * - Managing deployment configurations
 * - Handling deployment rollbacks
 * - Monitoring deployment health
 * - Generating deployment reports
 * - Tracking deployment history
 * - Ensuring deployment security
 * 
 * @see docs/mcp/agents/operations/deployment/DeploymentAutomationAgent.md
 */
class DeploymentAutomationAgent extends BaseCodeAnalysisAgent
{
    private array $metrics = [
        'deployments_completed' => 0,
        'deployments_failed' => 0,
        'rollbacks_performed' => 0,
        'deployment_time' => 0,
        'verification_time' => 0,
        'rollback_time' => 0,
        'deployment_success_rate' => 0,
        'deployments_by_environment' => [],
        'deployments_by_status' => [],
        'deployments_by_type' => []
    ];

    private array $report = [];
    private array $deploymentData = [];
    private array $deploymentHistory = [];
    private array $deploymentConfigs = [];
    private array $deploymentChecks = [];

    private array $deploymentTools = [
        'git' => 'git',
        'composer' => 'composer',
        'npm' => 'npm',
        'artisan' => 'php artisan',
        'phpunit' => 'vendor/bin/phpunit',
    ];

    public function __construct(
        HealthMonitor $healthMonitor,
        AgentLifecycleManager $lifecycleManager,
        LoggerInterface $logger
    ) {
        parent::__construct($healthMonitor, $lifecycleManager, $logger);
    }

    /**
     * Get the agent's metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Deploy to an environment
     */
    public function deploy(string $environment, array $config = []): array
    {
        $this->logger->info("Starting deployment to $environment");
        
        try {
            $this->validateEnvironment($environment);
            $this->validateConfig($config);
            $this->preDeploymentChecks($environment);
            $this->executeDeployment($environment, $config);
            $this->postDeploymentVerification($environment);
            $this->updateDeploymentHistory($environment, $config);
        } catch (\Throwable $e) {
            $this->logger->error("Deployment failed: " . $e->getMessage());
            $this->logDeploymentError($environment, $e);
            $this->handleDeploymentFailure($environment);
            throw $e;
        }

        $this->report = [
            'metrics' => $this->metrics,
            'deployment_data' => $this->deploymentData,
            'deployment_history' => $this->deploymentHistory,
            'deployment_configs' => $this->deploymentConfigs,
            'deployment_checks' => $this->deploymentChecks,
            'summary' => $this->generateSummary(),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->report;
    }

    /**
     * Rollback a deployment
     */
    public function rollback(string $environment, string $version): array
    {
        $this->logger->info("Starting rollback to version $version in $environment");

        try {
            $this->validateEnvironment($environment);
            $this->validateVersion($version);
            $this->preRollbackChecks($environment, $version);
            $this->executeRollback($environment, $version);
            $this->postRollbackVerification($environment);
            $this->updateRollbackHistory($environment, $version);
        } catch (\Throwable $e) {
            $this->logger->error("Rollback failed: " . $e->getMessage());
            $this->logRollbackError($environment, $e);
            $this->handleRollbackFailure($environment);
            throw $e;
        }

        return $this->report;
    }

    /**
     * Validate environment
     */
    private function validateEnvironment(string $environment): void
    {
        if (!in_array($environment, ['development', 'testing', 'staging', 'production'])) {
            throw new \InvalidArgumentException("Invalid environment: $environment");
        }

        if ($environment === 'production' && !$this->isProductionDeploymentAllowed()) {
            throw new \RuntimeException('Production deployment not allowed');
        }
    }

    /**
     * Validate configuration
     */
    private function validateConfig(array $config): void
    {
        $required = ['version', 'branch', 'commit'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new \InvalidArgumentException("Missing required config field: $field");
            }
        }
    }

    /**
     * Pre-deployment checks
     */
    private function preDeploymentChecks(string $environment): void
    {
        $this->logger->info("Running pre-deployment checks for $environment");

        $checks = [
            'git_status' => $this->checkGitStatus(),
            'dependencies' => $this->checkDependencies(),
            'tests' => $this->runTests(),
            'migrations' => $this->checkMigrations(),
            'environment' => $this->checkEnvironment($environment),
            'resources' => $this->checkResources($environment)
        ];

        $this->deploymentChecks[$environment] = $checks;

        foreach ($checks as $check => $result) {
            if (!$result['success']) {
                throw new \RuntimeException("Pre-deployment check failed: $check");
            }
        }
    }

    /**
     * Execute deployment
     */
    private function executeDeployment(string $environment, array $config): void
    {
        $startTime = microtime(true);

        try {
            // Pull latest changes
            $this->executeGitPull($config['branch']);

            // Install dependencies
            $this->installDependencies();

            // Run migrations
            $this->runMigrations();

            // Clear caches
            $this->clearCaches();

            // Update version
            $this->updateVersion($config['version']);

            $this->metrics['deployment_time'] = microtime(true) - $startTime;
            $this->metrics['deployments_completed']++;
            $this->metrics['deployments_by_environment'][$environment] = 
                ($this->metrics['deployments_by_environment'][$environment] ?? 0) + 1;
            $this->metrics['deployments_by_status']['success'] = 
                ($this->metrics['deployments_by_status']['success'] ?? 0) + 1;

        } catch (\Throwable $e) {
            $this->metrics['deployments_failed']++;
            $this->metrics['deployments_by_status']['failed'] = 
                ($this->metrics['deployments_by_status']['failed'] ?? 0) + 1;
            throw $e;
        }
    }

    /**
     * Post-deployment verification
     */
    private function postDeploymentVerification(string $environment): void
    {
        $startTime = microtime(true);

        $verifications = [
            'application_health' => $this->verifyApplicationHealth(),
            'database_health' => $this->verifyDatabaseHealth(),
            'cache_health' => $this->verifyCacheHealth(),
            'queue_health' => $this->verifyQueueHealth(),
            'service_health' => $this->verifyServiceHealth()
        ];

        foreach ($verifications as $verification => $result) {
            if (!$result['success']) {
                throw new \RuntimeException("Post-deployment verification failed: $verification");
            }
        }

        $this->metrics['verification_time'] = microtime(true) - $startTime;
    }

    /**
     * Update deployment history
     */
    private function updateDeploymentHistory(string $environment, array $config): void
    {
        $this->deploymentHistory[] = [
            'environment' => $environment,
            'version' => $config['version'],
            'branch' => $config['branch'],
            'commit' => $config['commit'],
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'success',
            'metrics' => $this->metrics
        ];
    }

    /**
     * Log deployment error
     */
    private function logDeploymentError(string $environment, \Throwable $error): void
    {
        $errorLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => $environment,
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString()
        ];

        $errorFile = '.errors/' . $environment . '_deployment_error.log';
        file_put_contents($errorFile, json_encode($errorLog, JSON_PRETTY_PRINT));
    }

    /**
     * Handle deployment failure
     */
    private function handleDeploymentFailure(string $environment): void
    {
        $this->logger->error("Deployment failed for $environment");
        $this->metrics['deployments_failed']++;
        $this->metrics['deployments_by_status']['failed'] = 
            ($this->metrics['deployments_by_status']['failed'] ?? 0) + 1;
    }

    /**
     * Generate summary
     */
    private function generateSummary(): array
    {
        return [
            'deployments_completed' => $this->metrics['deployments_completed'],
            'deployments_failed' => $this->metrics['deployments_failed'],
            'rollbacks_performed' => $this->metrics['rollbacks_performed'],
            'deployment_time' => $this->metrics['deployment_time'],
            'verification_time' => $this->metrics['verification_time'],
            'rollback_time' => $this->metrics['rollback_time'],
            'deployment_success_rate' => $this->calculateSuccessRate(),
            'deployments_by_environment' => $this->metrics['deployments_by_environment'],
            'deployments_by_status' => $this->metrics['deployments_by_status'],
            'deployments_by_type' => $this->metrics['deployments_by_type']
        ];
    }

    /**
     * Calculate deployment success rate
     */
    private function calculateSuccessRate(): float
    {
        $total = $this->metrics['deployments_completed'] + $this->metrics['deployments_failed'];
        if ($total === 0) {
            return 0;
        }
        return ($this->metrics['deployments_completed'] / $total) * 100;
    }

    /**
     * Check if production deployment is allowed
     */
    private function isProductionDeploymentAllowed(): bool
    {
        return app()->environment() !== 'production' || 
               config('mcp.allow_production_deployment', false);
    }

    /**
     * Execute git pull
     */
    private function executeGitPull(string $branch): void
    {
        $process = new Process([
            $this->deploymentTools['git'],
            'pull',
            'origin',
            $branch
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Git pull failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Install dependencies
     */
    private function installDependencies(): void
    {
        // Install PHP dependencies
        $process = new Process([
            $this->deploymentTools['composer'],
            'install',
            '--no-dev',
            '--optimize-autoloader'
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Composer install failed: " . $process->getErrorOutput());
        }

        // Install NPM dependencies
        $process = new Process([
            $this->deploymentTools['npm'],
            'install',
            '--production'
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("NPM install failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Run migrations
     */
    private function runMigrations(): void
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'migrate',
            '--force'
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Migration failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Clear caches
     */
    private function clearCaches(): void
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'optimize:clear'
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Cache clear failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Update version
     */
    private function updateVersion(string $version): void
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'version:set',
            $version
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Version update failed: " . $process->getErrorOutput());
        }
    }

    /**
     * Check git status
     */
    private function checkGitStatus(): array
    {
        $process = new Process([
            $this->deploymentTools['git'],
            'status'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    /**
     * Check dependencies
     */
    private function checkDependencies(): array
    {
        $process = new Process([
            $this->deploymentTools['composer'],
            'validate'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    /**
     * Run tests
     */
    private function runTests(): array
    {
        $process = new Process([
            $this->deploymentTools['phpunit'],
            '--stop-on-failure'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    /**
     * Check migrations
     */
    private function checkMigrations(): array
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'migrate:status'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    /**
     * Check environment
     */
    private function checkEnvironment(string $environment): array
    {
        return [
            'success' => app()->environment() === $environment,
            'output' => "Current environment: " . app()->environment()
        ];
    }

    /**
     * Check resources
     */
    private function checkResources(string $environment): array
    {
        $checks = [
            'disk_space' => $this->checkDiskSpace(),
            'memory' => $this->checkMemory(),
            'cpu' => $this->checkCpu(),
            'network' => $this->checkNetwork()
        ];

        return [
            'success' => !in_array(false, array_column($checks, 'success')),
            'output' => $checks
        ];
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace(): array
    {
        $free = disk_free_space(base_path());
        $total = disk_total_space(base_path());
        $used = $total - $free;
        $percent = ($used / $total) * 100;

        return [
            'success' => $percent < 90,
            'output' => [
                'free' => $free,
                'total' => $total,
                'used' => $used,
                'percent' => $percent
            ]
        ];
    }

    /**
     * Check memory
     */
    private function checkMemory(): array
    {
        $memory = memory_get_usage(true);
        $limit = ini_get('memory_limit');
        $limit = $this->parseMemoryLimit($limit);

        return [
            'success' => $memory < ($limit * 0.9),
            'output' => [
                'used' => $memory,
                'limit' => $limit
            ]
        ];
    }

    /**
     * Check CPU
     */
    private function checkCpu(): array
    {
        $load = sys_getloadavg();

        return [
            'success' => $load[0] < 80,
            'output' => [
                'load' => $load
            ]
        ];
    }

    /**
     * Check network
     */
    private function checkNetwork(): array
    {
        $hosts = [
            'github.com',
            'packagist.org',
            'npmjs.org'
        ];

        $results = [];
        foreach ($hosts as $host) {
            $results[$host] = $this->pingHost($host);
        }

        return [
            'success' => !in_array(false, $results),
            'output' => $results
        ];
    }

    /**
     * Parse memory limit
     */
    private function parseMemoryLimit(string $limit): int
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Ping host
     */
    private function pingHost(string $host): bool
    {
        $process = new Process([
            'ping',
            '-c',
            '1',
            $host
        ]);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Verify application health
     */
    private function verifyApplicationHealth(): array
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'health:check'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    /**
     * Verify database health
     */
    private function verifyDatabaseHealth(): array
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'db:health'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    /**
     * Verify cache health
     */
    private function verifyCacheHealth(): array
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'cache:health'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    /**
     * Verify queue health
     */
    private function verifyQueueHealth(): array
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'queue:health'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }

    /**
     * Verify service health
     */
    private function verifyServiceHealth(): array
    {
        $process = new Process([
            $this->deploymentTools['artisan'],
            'service:health'
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput()
        ];
    }
} 