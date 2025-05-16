<?php

namespace App\MCP\Agents\Operations\Health;

use App\MCP\Agents\Development\CodeAnalysis\BaseCodeAnalysisAgent;
use App\MCP\Core\Services\HealthMonitor;
use App\MCP\Core\Services\AgentLifecycleManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Service Health Agent
 * 
 * This agent is responsible for:
 * - Monitoring service health
 * - Checking service dependencies
 * - Validating service configurations
 * - Tracking service metrics
 * - Generating health reports
 * - Managing service alerts
 * - Ensuring service availability
 * 
 * @see docs/mcp/agents/operations/health/ServiceHealthAgent.md
 */
class ServiceHealthAgent extends BaseCodeAnalysisAgent
{
    private array $metrics = [
        'services_checked' => 0,
        'services_healthy' => 0,
        'services_unhealthy' => 0,
        'services_degraded' => 0,
        'check_time' => 0,
        'response_time' => 0,
        'error_rate' => 0,
        'services_by_status' => [],
        'services_by_type' => [],
        'services_by_environment' => []
    ];

    private array $report = [];
    private array $serviceData = [];
    private array $serviceHistory = [];
    private array $serviceConfigs = [];
    private array $serviceChecks = [];

    private array $serviceTypes = [
        'web' => 'Web Service',
        'api' => 'API Service',
        'queue' => 'Queue Service',
        'cache' => 'Cache Service',
        'database' => 'Database Service',
        'storage' => 'Storage Service',
        'search' => 'Search Service',
        'auth' => 'Auth Service'
    ];

    private array $healthChecks = [
        'availability' => 'Service Availability',
        'response_time' => 'Response Time',
        'error_rate' => 'Error Rate',
        'resource_usage' => 'Resource Usage',
        'dependency_health' => 'Dependency Health',
        'configuration' => 'Configuration',
        'security' => 'Security',
        'performance' => 'Performance'
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
     * Check service health
     */
    public function checkHealth(string $service, array $config = []): array
    {
        $this->logger->info("Starting health check for service: $service");
        
        try {
            $this->validateService($service);
            $this->validateConfig($config);
            $this->preHealthChecks($service);
            $this->executeHealthChecks($service, $config);
            $this->postHealthChecks($service);
            $this->updateServiceHistory($service, $config);
        } catch (\Throwable $e) {
            $this->logger->error("Health check failed: " . $e->getMessage());
            $this->logHealthError($service, $e);
            $this->handleHealthFailure($service);
            throw $e;
        }

        $this->report = [
            'metrics' => $this->metrics,
            'service_data' => $this->serviceData,
            'service_history' => $this->serviceHistory,
            'service_configs' => $this->serviceConfigs,
            'service_checks' => $this->serviceChecks,
            'summary' => $this->generateSummary(),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->report;
    }

    /**
     * Validate service
     */
    private function validateService(string $service): void
    {
        if (!isset($this->serviceTypes[$service])) {
            throw new \InvalidArgumentException("Invalid service: $service");
        }
    }

    /**
     * Validate configuration
     */
    private function validateConfig(array $config): void
    {
        $required = ['environment', 'timeout', 'thresholds'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new \InvalidArgumentException("Missing required config field: $field");
            }
        }
    }

    /**
     * Pre-health checks
     */
    private function preHealthChecks(string $service): void
    {
        $this->logger->info("Running pre-health checks for $service");

        $checks = [
            'service_status' => $this->checkServiceStatus($service),
            'dependencies' => $this->checkDependencies($service),
            'configuration' => $this->checkConfiguration($service),
            'resources' => $this->checkResources($service)
        ];

        $this->serviceChecks[$service] = $checks;

        foreach ($checks as $check => $result) {
            if (!$result['success']) {
                throw new \RuntimeException("Pre-health check failed: $check");
            }
        }
    }

    /**
     * Execute health checks
     */
    private function executeHealthChecks(string $service, array $config): void
    {
        $startTime = microtime(true);

        try {
            $checks = [
                'availability' => $this->checkAvailability($service),
                'response_time' => $this->checkResponseTime($service),
                'error_rate' => $this->checkErrorRate($service),
                'resource_usage' => $this->checkResourceUsage($service),
                'dependency_health' => $this->checkDependencyHealth($service),
                'configuration' => $this->checkConfiguration($service),
                'security' => $this->checkSecurity($service),
                'performance' => $this->checkPerformance($service)
            ];

            $this->serviceData[$service] = $checks;

            $healthy = true;
            $degraded = false;

            foreach ($checks as $check => $result) {
                if (!$result['success']) {
                    $healthy = false;
                }
                if ($result['degraded']) {
                    $degraded = true;
                }
            }

            if ($healthy) {
                $this->metrics['services_healthy']++;
                $this->metrics['services_by_status']['healthy'] = 
                    ($this->metrics['services_by_status']['healthy'] ?? 0) + 1;
            } elseif ($degraded) {
                $this->metrics['services_degraded']++;
                $this->metrics['services_by_status']['degraded'] = 
                    ($this->metrics['services_by_status']['degraded'] ?? 0) + 1;
            } else {
                $this->metrics['services_unhealthy']++;
                $this->metrics['services_by_status']['unhealthy'] = 
                    ($this->metrics['services_by_status']['unhealthy'] ?? 0) + 1;
            }

            $this->metrics['services_checked']++;
            $this->metrics['check_time'] = microtime(true) - $startTime;
            $this->metrics['services_by_type'][$service] = 
                ($this->metrics['services_by_type'][$service] ?? 0) + 1;

        } catch (\Throwable $e) {
            $this->metrics['services_unhealthy']++;
            $this->metrics['services_by_status']['unhealthy'] = 
                ($this->metrics['services_by_status']['unhealthy'] ?? 0) + 1;
            throw $e;
        }
    }

    /**
     * Post-health checks
     */
    private function postHealthChecks(string $service): void
    {
        $this->logger->info("Running post-health checks for $service");

        $verifications = [
            'service_status' => $this->verifyServiceStatus($service),
            'dependencies' => $this->verifyDependencies($service),
            'configuration' => $this->verifyConfiguration($service),
            'resources' => $this->verifyResources($service)
        ];

        foreach ($verifications as $verification => $result) {
            if (!$result['success']) {
                throw new \RuntimeException("Post-health check failed: $verification");
            }
        }
    }

    /**
     * Update service history
     */
    private function updateServiceHistory(string $service, array $config): void
    {
        $this->serviceHistory[] = [
            'service' => $service,
            'environment' => $config['environment'],
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $this->getServiceStatus($service),
            'metrics' => $this->metrics
        ];
    }

    /**
     * Log health error
     */
    private function logHealthError(string $service, \Throwable $error): void
    {
        $errorLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'service' => $service,
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString()
        ];

        $errorFile = '.errors/' . $service . '_health_error.log';
        file_put_contents($errorFile, json_encode($errorLog, JSON_PRETTY_PRINT));
    }

    /**
     * Handle health failure
     */
    private function handleHealthFailure(string $service): void
    {
        $this->logger->error("Health check failed for $service");
        $this->metrics['services_unhealthy']++;
        $this->metrics['services_by_status']['unhealthy'] = 
            ($this->metrics['services_by_status']['unhealthy'] ?? 0) + 1;
    }

    /**
     * Generate summary
     */
    private function generateSummary(): array
    {
        return [
            'services_checked' => $this->metrics['services_checked'],
            'services_healthy' => $this->metrics['services_healthy'],
            'services_unhealthy' => $this->metrics['services_unhealthy'],
            'services_degraded' => $this->metrics['services_degraded'],
            'check_time' => $this->metrics['check_time'],
            'response_time' => $this->metrics['response_time'],
            'error_rate' => $this->metrics['error_rate'],
            'services_by_status' => $this->metrics['services_by_status'],
            'services_by_type' => $this->metrics['services_by_type'],
            'services_by_environment' => $this->metrics['services_by_environment']
        ];
    }

    /**
     * Get service status
     */
    private function getServiceStatus(string $service): string
    {
        if ($this->metrics['services_by_status']['healthy'] ?? 0 > 0) {
            return 'healthy';
        }
        if ($this->metrics['services_by_status']['degraded'] ?? 0 > 0) {
            return 'degraded';
        }
        return 'unhealthy';
    }

    /**
     * Check service status
     */
    private function checkServiceStatus(string $service): array
    {
        $process = new Process([
            'systemctl',
            'status',
            $service
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
    private function checkDependencies(string $service): array
    {
        $dependencies = $this->getServiceDependencies($service);
        $results = [];

        foreach ($dependencies as $dependency) {
            $results[$dependency] = $this->checkDependency($dependency);
        }

        return [
            'success' => !in_array(false, array_column($results, 'success')),
            'output' => $results
        ];
    }

    /**
     * Check configuration
     */
    private function checkConfiguration(string $service): array
    {
        $config = $this->getServiceConfig($service);
        $results = [];

        foreach ($config as $key => $value) {
            $results[$key] = $this->validateConfigValue($key, $value);
        }

        return [
            'success' => !in_array(false, array_column($results, 'success')),
            'output' => $results
        ];
    }

    /**
     * Check resources
     */
    private function checkResources(string $service): array
    {
        $checks = [
            'cpu' => $this->checkCpu($service),
            'memory' => $this->checkMemory($service),
            'disk' => $this->checkDisk($service),
            'network' => $this->checkNetwork($service)
        ];

        return [
            'success' => !in_array(false, array_column($checks, 'success')),
            'output' => $checks
        ];
    }

    /**
     * Check availability
     */
    private function checkAvailability(string $service): array
    {
        $endpoint = $this->getServiceEndpoint($service);
        $process = new Process([
            'curl',
            '-I',
            $endpoint
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'degraded' => false
        ];
    }

    /**
     * Check response time
     */
    private function checkResponseTime(string $service): array
    {
        $endpoint = $this->getServiceEndpoint($service);
        $startTime = microtime(true);

        $process = new Process([
            'curl',
            '-s',
            '-w',
            '%{time_total}',
            $endpoint
        ]);
        $process->run();

        $responseTime = (float) $process->getOutput();
        $this->metrics['response_time'] = $responseTime;

        return [
            'success' => $responseTime < 1.0,
            'output' => $responseTime,
            'degraded' => $responseTime >= 0.5 && $responseTime < 1.0
        ];
    }

    /**
     * Check error rate
     */
    private function checkErrorRate(string $service): array
    {
        $errors = $this->getServiceErrors($service);
        $total = $this->getServiceTotal($service);
        $errorRate = ($errors / $total) * 100;

        $this->metrics['error_rate'] = $errorRate;

        return [
            'success' => $errorRate < 1.0,
            'output' => $errorRate,
            'degraded' => $errorRate >= 0.1 && $errorRate < 1.0
        ];
    }

    /**
     * Check resource usage
     */
    private function checkResourceUsage(string $service): array
    {
        $checks = [
            'cpu' => $this->checkCpu($service),
            'memory' => $this->checkMemory($service),
            'disk' => $this->checkDisk($service),
            'network' => $this->checkNetwork($service)
        ];

        $degraded = false;
        foreach ($checks as $check) {
            if ($check['degraded']) {
                $degraded = true;
                break;
            }
        }

        return [
            'success' => !in_array(false, array_column($checks, 'success')),
            'output' => $checks,
            'degraded' => $degraded
        ];
    }

    /**
     * Check dependency health
     */
    private function checkDependencyHealth(string $service): array
    {
        $dependencies = $this->getServiceDependencies($service);
        $results = [];

        foreach ($dependencies as $dependency) {
            $results[$dependency] = $this->checkDependency($dependency);
        }

        $degraded = false;
        foreach ($results as $result) {
            if ($result['degraded']) {
                $degraded = true;
                break;
            }
        }

        return [
            'success' => !in_array(false, array_column($results, 'success')),
            'output' => $results,
            'degraded' => $degraded
        ];
    }

    /**
     * Check security
     */
    private function checkSecurity(string $service): array
    {
        $checks = [
            'ssl' => $this->checkSsl($service),
            'authentication' => $this->checkAuthentication($service),
            'authorization' => $this->checkAuthorization($service),
            'encryption' => $this->checkEncryption($service)
        ];

        return [
            'success' => !in_array(false, array_column($checks, 'success')),
            'output' => $checks,
            'degraded' => false
        ];
    }

    /**
     * Check performance
     */
    private function checkPerformance(string $service): array
    {
        $checks = [
            'response_time' => $this->checkResponseTime($service),
            'throughput' => $this->checkThroughput($service),
            'concurrency' => $this->checkConcurrency($service),
            'resource_usage' => $this->checkResourceUsage($service)
        ];

        $degraded = false;
        foreach ($checks as $check) {
            if ($check['degraded']) {
                $degraded = true;
                break;
            }
        }

        return [
            'success' => !in_array(false, array_column($checks, 'success')),
            'output' => $checks,
            'degraded' => $degraded
        ];
    }

    /**
     * Get service dependencies
     */
    private function getServiceDependencies(string $service): array
    {
        // This would be configured per service
        return [
            'database',
            'cache',
            'queue'
        ];
    }

    /**
     * Get service config
     */
    private function getServiceConfig(string $service): array
    {
        // This would be configured per service
        return [
            'timeout' => 30,
            'retries' => 3,
            'concurrency' => 10
        ];
    }

    /**
     * Get service endpoint
     */
    private function getServiceEndpoint(string $service): string
    {
        // This would be configured per service
        return "http://localhost:8080/$service/health";
    }

    /**
     * Get service errors
     */
    private function getServiceErrors(string $service): int
    {
        // This would be implemented to get actual error count
        return 0;
    }

    /**
     * Get service total
     */
    private function getServiceTotal(string $service): int
    {
        // This would be implemented to get actual total count
        return 1000;
    }

    /**
     * Check CPU
     */
    private function checkCpu(string $service): array
    {
        $process = new Process([
            'ps',
            '-p',
            $this->getServicePid($service),
            '-o',
            '%cpu'
        ]);
        $process->run();

        $cpu = (float) $process->getOutput();

        return [
            'success' => $cpu < 80,
            'output' => $cpu,
            'degraded' => $cpu >= 60 && $cpu < 80
        ];
    }

    /**
     * Check memory
     */
    private function checkMemory(string $service): array
    {
        $process = new Process([
            'ps',
            '-p',
            $this->getServicePid($service),
            '-o',
            '%mem'
        ]);
        $process->run();

        $memory = (float) $process->getOutput();

        return [
            'success' => $memory < 80,
            'output' => $memory,
            'degraded' => $memory >= 60 && $memory < 80
        ];
    }

    /**
     * Check disk
     */
    private function checkDisk(string $service): array
    {
        $process = new Process([
            'df',
            '-h',
            $this->getServicePath($service)
        ]);
        $process->run();

        $disk = (float) $process->getOutput();

        return [
            'success' => $disk < 80,
            'output' => $disk,
            'degraded' => $disk >= 60 && $disk < 80
        ];
    }

    /**
     * Check network
     */
    private function checkNetwork(string $service): array
    {
        $process = new Process([
            'netstat',
            '-an',
            '|',
            'grep',
            $this->getServicePort($service)
        ]);
        $process->run();

        $connections = (int) $process->getOutput();

        return [
            'success' => $connections < 1000,
            'output' => $connections,
            'degraded' => $connections >= 800 && $connections < 1000
        ];
    }

    /**
     * Check SSL
     */
    private function checkSsl(string $service): array
    {
        $endpoint = $this->getServiceEndpoint($service);
        $process = new Process([
            'curl',
            '-I',
            '--ssl',
            $endpoint
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'degraded' => false
        ];
    }

    /**
     * Check authentication
     */
    private function checkAuthentication(string $service): array
    {
        $endpoint = $this->getServiceEndpoint($service);
        $process = new Process([
            'curl',
            '-I',
            '-u',
            'test:test',
            $endpoint
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'degraded' => false
        ];
    }

    /**
     * Check authorization
     */
    private function checkAuthorization(string $service): array
    {
        $endpoint = $this->getServiceEndpoint($service);
        $process = new Process([
            'curl',
            '-I',
            '-H',
            'Authorization: Bearer test',
            $endpoint
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'degraded' => false
        ];
    }

    /**
     * Check encryption
     */
    private function checkEncryption(string $service): array
    {
        $endpoint = $this->getServiceEndpoint($service);
        $process = new Process([
            'curl',
            '-I',
            '--tlsv1.2',
            $endpoint
        ]);
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'degraded' => false
        ];
    }

    /**
     * Check throughput
     */
    private function checkThroughput(string $service): array
    {
        $endpoint = $this->getServiceEndpoint($service);
        $process = new Process([
            'ab',
            '-n',
            '1000',
            '-c',
            '10',
            $endpoint
        ]);
        $process->run();

        $throughput = (float) $process->getOutput();

        return [
            'success' => $throughput > 100,
            'output' => $throughput,
            'degraded' => $throughput >= 50 && $throughput <= 100
        ];
    }

    /**
     * Check concurrency
     */
    private function checkConcurrency(string $service): array
    {
        $endpoint = $this->getServiceEndpoint($service);
        $process = new Process([
            'ab',
            '-n',
            '1000',
            '-c',
            '100',
            $endpoint
        ]);
        $process->run();

        $concurrency = (float) $process->getOutput();

        return [
            'success' => $concurrency > 50,
            'output' => $concurrency,
            'degraded' => $concurrency >= 25 && $concurrency <= 50
        ];
    }

    /**
     * Get service PID
     */
    private function getServicePid(string $service): string
    {
        // This would be implemented to get actual PID
        return '1234';
    }

    /**
     * Get service path
     */
    private function getServicePath(string $service): string
    {
        // This would be implemented to get actual path
        return '/var/www/html';
    }

    /**
     * Get service port
     */
    private function getServicePort(string $service): string
    {
        // This would be implemented to get actual port
        return '8080';
    }

    /**
     * Validate config value
     */
    private function validateConfigValue(string $key, $value): array
    {
        // This would be implemented to validate actual config values
        return [
            'success' => true,
            'output' => $value
        ];
    }

    /**
     * Check dependency
     */
    private function checkDependency(string $dependency): array
    {
        // This would be implemented to check actual dependencies
        return [
            'success' => true,
            'output' => 'OK',
            'degraded' => false
        ];
    }
} 