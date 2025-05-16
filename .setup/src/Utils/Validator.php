<?php

namespace Setup\Utils;

class Validator {
    private array $config;
    private Logger $logger;
    private array $errors = [];

    public function __construct(array $config) {
        $this->config = $config;
        $this->logger = new Logger();
    }

    public function validate(): array {
        $this->errors = [];
        
        $this->validateEnvironment();
        $this->validateServices();
        $this->validateMonitoring();
        $this->validateTesting();
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }

    private function validateEnvironment(): void {
        if (!isset($this->config['environment'])) {
            $this->addError('Environment type is required');
            return;
        }

        $validEnvironments = ['local', 'codespaces', 'hybrid'];
        if (!in_array($this->config['environment'], $validEnvironments)) {
            $this->addError('Invalid environment type');
        }

        switch ($this->config['environment']) {
            case 'local':
                $this->validateLocalEnvironment();
                break;
            case 'codespaces':
                $this->validateCodespacesEnvironment();
                break;
            case 'hybrid':
                $this->validateHybridEnvironment();
                break;
        }
    }

    private function validateLocalEnvironment(): void {
        $requiredServices = ['database', 'cache', 'queue'];
        foreach ($requiredServices as $service) {
            if (!isset($this->config[$service])) {
                $this->addError("{$service} configuration is required for local environment");
            }
        }

        // Validate database configuration
        if (isset($this->config['database'])) {
            $this->validateDatabaseConfig($this->config['database']);
        }

        // Validate cache configuration
        if (isset($this->config['cache'])) {
            $this->validateCacheConfig($this->config['cache']);
        }

        // Validate queue configuration
        if (isset($this->config['queue'])) {
            $this->validateQueueConfig($this->config['queue']);
        }
    }

    private function validateCodespacesEnvironment(): void {
        if (!isset($this->config['github'])) {
            $this->addError('GitHub configuration is required for Codespaces environment');
        } else {
            $this->validateGitHubConfig($this->config['github']);
        }

        if (!isset($this->config['codespaces'])) {
            $this->addError('Codespaces configuration is required');
        } else {
            $this->validateCodespacesConfig($this->config['codespaces']);
        }
    }

    private function validateHybridEnvironment(): void {
        // Validate local components
        $this->validateLocalEnvironment();

        // Validate Codespaces components
        $this->validateCodespacesEnvironment();

        // Validate service distribution
        if (!isset($this->config['distribution'])) {
            $this->addError('Service distribution configuration is required for hybrid environment');
        } else {
            $this->validateServiceDistribution($this->config['distribution']);
        }
    }

    private function validateServices(): void {
        if (!isset($this->config['services'])) {
            return;
        }

        foreach ($this->config['services'] as $service => $config) {
            switch ($service) {
                case 'api':
                    $this->validateApiConfig($config);
                    break;
                case 'database':
                    $this->validateDatabaseConfig($config);
                    break;
                case 'cache':
                    $this->validateCacheConfig($config);
                    break;
                case 'queue':
                    $this->validateQueueConfig($config);
                    break;
                case 'mail':
                    $this->validateMailConfig($config);
                    break;
                default:
                    $this->addError("Unknown service type: {$service}");
            }
        }
    }

    private function validateApiConfig(array $config): void {
        $requiredFields = ['port', 'workers', 'timeout'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("API configuration missing required field: {$field}");
            }
        }

        if (isset($config['port']) && !is_numeric($config['port'])) {
            $this->addError('API port must be a number');
        }

        if (isset($config['workers']) && !is_numeric($config['workers'])) {
            $this->addError('API workers must be a number');
        }

        if (isset($config['timeout']) && !is_numeric($config['timeout'])) {
            $this->addError('API timeout must be a number');
        }
    }

    private function validateDatabaseConfig(array $config): void {
        $requiredFields = ['host', 'port', 'name', 'user', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Database configuration missing required field: {$field}");
            }
        }

        if (isset($config['port']) && !is_numeric($config['port'])) {
            $this->addError('Database port must be a number');
        }
    }

    private function validateCacheConfig(array $config): void {
        $requiredFields = ['host', 'port'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Cache configuration missing required field: {$field}");
            }
        }

        if (isset($config['port']) && !is_numeric($config['port'])) {
            $this->addError('Cache port must be a number');
        }
    }

    private function validateQueueConfig(array $config): void {
        $requiredFields = ['host', 'port', 'user', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Queue configuration missing required field: {$field}");
            }
        }

        if (isset($config['port']) && !is_numeric($config['port'])) {
            $this->addError('Queue port must be a number');
        }
    }

    private function validateMailConfig(array $config): void {
        $requiredFields = ['smtp_host', 'smtp_port', 'encryption'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Mail configuration missing required field: {$field}");
            }
        }

        if (isset($config['smtp_port']) && !is_numeric($config['smtp_port'])) {
            $this->addError('SMTP port must be a number');
        }

        if (isset($config['encryption'])) {
            $validEncryption = ['tls', 'ssl', 'none'];
            if (!in_array($config['encryption'], $validEncryption)) {
                $this->addError('Invalid SMTP encryption type');
            }
        }
    }

    private function validateGitHubConfig(array $config): void {
        $requiredFields = ['token', 'repository', 'branch'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("GitHub configuration missing required field: {$field}");
            }
        }

        if (isset($config['token']) && empty($config['token'])) {
            $this->addError('GitHub token cannot be empty');
        }
    }

    private function validateCodespacesConfig(array $config): void {
        $requiredFields = ['machine', 'region'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Codespaces configuration missing required field: {$field}");
            }
        }

        if (isset($config['machine'])) {
            $validMachines = ['basic', 'standard', 'premium'];
            if (!in_array($config['machine'], $validMachines)) {
                $this->addError('Invalid Codespaces machine type');
            }
        }
    }

    private function validateServiceDistribution(array $config): void {
        $requiredFields = ['local', 'codespaces'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Service distribution missing required field: {$field}");
            }
        }

        if (isset($config['local']) && isset($config['codespaces'])) {
            $allServices = array_merge($config['local'], $config['codespaces']);
            $uniqueServices = array_unique($allServices);
            
            if (count($allServices) !== count($uniqueServices)) {
                $this->addError('Services cannot be distributed to both local and Codespaces environments');
            }
        }
    }

    private function validateMonitoring(): void {
        if (!isset($this->config['monitoring'])) {
            return;
        }

        $config = $this->config['monitoring'];

        if (isset($config['health_checks'])) {
            $this->validateHealthChecks($config['health_checks']);
        }

        if (isset($config['metrics'])) {
            $this->validateMetrics($config['metrics']);
        }

        if (isset($config['alerts'])) {
            $this->validateAlerts($config['alerts']);
        }

        if (isset($config['logging'])) {
            $this->validateLogging($config['logging']);
        }
    }

    private function validateHealthChecks(array $config): void {
        $requiredFields = ['interval', 'timeout'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Health checks configuration missing required field: {$field}");
            }
        }

        if (isset($config['interval']) && !is_numeric($config['interval'])) {
            $this->addError('Health check interval must be a number');
        }

        if (isset($config['timeout']) && !is_numeric($config['timeout'])) {
            $this->addError('Health check timeout must be a number');
        }
    }

    private function validateMetrics(array $config): void {
        $requiredFields = ['collection_interval', 'retention_days'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Metrics configuration missing required field: {$field}");
            }
        }

        if (isset($config['collection_interval']) && !is_numeric($config['collection_interval'])) {
            $this->addError('Metrics collection interval must be a number');
        }

        if (isset($config['retention_days']) && !is_numeric($config['retention_days'])) {
            $this->addError('Metrics retention days must be a number');
        }
    }

    private function validateAlerts(array $config): void {
        if (isset($config['enabled']) && $config['enabled']) {
            if (!isset($config['channels']) || empty($config['channels'])) {
                $this->addError('Alert channels are required when alerts are enabled');
            } else {
                $validChannels = ['email', 'slack', 'webhook'];
                foreach ($config['channels'] as $channel) {
                    if (!in_array($channel, $validChannels)) {
                        $this->addError("Invalid alert channel: {$channel}");
                    }
                }
            }
        }
    }

    private function validateLogging(array $config): void {
        $requiredFields = ['level', 'retention_days'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                $this->addError("Logging configuration missing required field: {$field}");
            }
        }

        if (isset($config['level'])) {
            $validLevels = ['debug', 'info', 'warning', 'error'];
            if (!in_array($config['level'], $validLevels)) {
                $this->addError('Invalid log level');
            }
        }

        if (isset($config['retention_days']) && !is_numeric($config['retention_days'])) {
            $this->addError('Log retention days must be a number');
        }
    }

    private function validateTesting(): void {
        if (!isset($this->config['testing'])) {
            return;
        }

        $config = $this->config['testing'];

        if (isset($config['suites'])) {
            $this->validateTestSuites($config['suites']);
        }

        if (isset($config['coverage'])) {
            $this->validateCoverage($config['coverage']);
        }

        if (isset($config['performance'])) {
            $this->validatePerformance($config['performance']);
        }
    }

    private function validateTestSuites(array $config): void {
        $requiredSuites = ['unit', 'integration', 'e2e'];
        foreach ($requiredSuites as $suite) {
            if (!isset($config[$suite])) {
                $this->addError("Test suite configuration missing required suite: {$suite}");
            }
        }
    }

    private function validateCoverage(array $config): void {
        if (isset($config['enabled']) && $config['enabled']) {
            if (!isset($config['threshold'])) {
                $this->addError('Coverage threshold is required when coverage is enabled');
            } elseif (!is_numeric($config['threshold'])) {
                $this->addError('Coverage threshold must be a number');
            } elseif ($config['threshold'] < 0 || $config['threshold'] > 100) {
                $this->addError('Coverage threshold must be between 0 and 100');
            }
        }
    }

    private function validatePerformance(array $config): void {
        if (isset($config['enabled']) && $config['enabled']) {
            if (!isset($config['threshold'])) {
                $this->addError('Performance threshold is required when performance testing is enabled');
            } elseif (!is_numeric($config['threshold'])) {
                $this->addError('Performance threshold must be a number');
            }
        }
    }

    private function addError(string $error): void {
        $this->errors[] = $error;
        $this->logger->error($error);
    }
} 