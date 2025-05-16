<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Setup\Config\ConfigurationManager;
use Setup\Utils\EnvironmentManager;
use Setup\Utils\ServiceManager;
use Setup\Utils\TestManager;
use Setup\Utils\Logger;

class Setup {
    private ConfigurationManager $config;
    private EnvironmentManager $env;
    private ServiceManager $services;
    private TestManager $tests;
    private Logger $logger;
    private array $setupState = [];

    public function __construct() {
        $this->logger = new Logger();
        $this->config = new ConfigurationManager();
        $this->env = new EnvironmentManager();
        $this->services = new ServiceManager();
        $this->tests = new TestManager();
    }

    public function run(): void {
        $this->logger->info('Starting setup process');
        
        try {
            $this->welcome();
            $this->configureEnvironment();
            $this->configureServices();
            $this->configureMonitoring();
            $this->configureTesting();
            $this->saveConfiguration();
            $this->runValidation();
            $this->showSummary();
        } catch (\Exception $e) {
            $this->logger->error('Setup failed: ' . $e->getMessage());
            $this->showError($e->getMessage());
            exit(1);
        }
    }

    private function welcome(): void {
        echo "\n=== Service Learning Management System Setup ===\n\n";
        echo "This script will guide you through the setup process.\n";
        echo "You can save your configuration for future use.\n\n";
    }

    private function configureEnvironment(): void {
        echo "\n=== Environment Configuration ===\n\n";
        
        // Load saved configuration if exists
        $savedConfig = $this->config->loadSavedConfig();
        if ($savedConfig) {
            $useSaved = $this->askYesNo('Found saved configuration. Use it?');
            if ($useSaved) {
                $this->setupState = $savedConfig;
                return;
            }
        }

        // Environment type
        $envType = $this->askChoice(
            'Select deployment environment:',
            ['local', 'codespaces', 'hybrid']
        );
        $this->setupState['environment'] = $envType;

        // Environment-specific settings
        switch ($envType) {
            case 'local':
                $this->configureLocalEnvironment();
                break;
            case 'codespaces':
                $this->configureCodespacesEnvironment();
                break;
            case 'hybrid':
                $this->configureHybridEnvironment();
                break;
        }
    }

    private function configureLocalEnvironment(): void {
        echo "\n=== Local Environment Configuration ===\n\n";
        
        // Database configuration
        $this->setupState['database'] = [
            'host' => $this->askInput('Database host', 'localhost'),
            'port' => $this->askInput('Database port', '5432'),
            'name' => $this->askInput('Database name', 'service_learning'),
            'user' => $this->askInput('Database user', 'postgres'),
            'password' => $this->askInput('Database password', '', true)
        ];

        // Cache configuration
        $this->setupState['cache'] = [
            'host' => $this->askInput('Cache host', 'localhost'),
            'port' => $this->askInput('Cache port', '6379'),
            'password' => $this->askInput('Cache password', '', true)
        ];

        // Queue configuration
        $this->setupState['queue'] = [
            'host' => $this->askInput('Queue host', 'localhost'),
            'port' => $this->askInput('Queue port', '5672'),
            'user' => $this->askInput('Queue user', 'guest'),
            'password' => $this->askInput('Queue password', '', true)
        ];
    }

    private function configureCodespacesEnvironment(): void {
        echo "\n=== Codespaces Environment Configuration ===\n\n";
        
        // GitHub configuration
        $this->setupState['github'] = [
            'token' => $this->askInput('GitHub token', '', true),
            'repository' => $this->askInput('Repository name', 'service-learning-management'),
            'branch' => $this->askInput('Branch name', 'main')
        ];

        // Codespaces configuration
        $this->setupState['codespaces'] = [
            'machine' => $this->askChoice(
                'Select machine type:',
                ['basic', 'standard', 'premium']
            ),
            'region' => $this->askInput('Region', 'us-east-1'),
            'auto_start' => $this->askYesNo('Auto-start Codespace?')
        ];
    }

    private function configureHybridEnvironment(): void {
        echo "\n=== Hybrid Environment Configuration ===\n\n";
        
        // Configure local services
        echo "Configure local services:\n";
        $this->configureLocalEnvironment();

        // Configure Codespaces services
        echo "\nConfigure Codespaces services:\n";
        $this->configureCodespacesEnvironment();

        // Service distribution
        $this->setupState['distribution'] = [
            'local' => $this->askMultiChoice(
                'Select services to run locally:',
                ['api', 'database', 'cache', 'queue', 'mail']
            ),
            'codespaces' => $this->askMultiChoice(
                'Select services to run in Codespaces:',
                ['api', 'database', 'cache', 'queue', 'mail']
            )
        ];
    }

    private function configureServices(): void {
        echo "\n=== Service Configuration ===\n\n";
        
        $services = ['api', 'database', 'cache', 'queue', 'mail'];
        foreach ($services as $service) {
            if ($this->askYesNo("Configure {$service} service?")) {
                $this->setupState['services'][$service] = $this->configureService($service);
            }
        }
    }

    private function configureService(string $service): array {
        $config = [];
        
        switch ($service) {
            case 'api':
                $config = [
                    'port' => $this->askInput('API port', '8080'),
                    'workers' => $this->askInput('Number of workers', '4'),
                    'timeout' => $this->askInput('Request timeout', '30')
                ];
                break;
                
            case 'database':
                $config = [
                    'max_connections' => $this->askInput('Max connections', '100'),
                    'pool_size' => $this->askInput('Connection pool size', '20')
                ];
                break;
                
            case 'cache':
                $config = [
                    'max_memory' => $this->askInput('Max memory (MB)', '512'),
                    'eviction_policy' => $this->askChoice(
                        'Eviction policy:',
                        ['lru', 'lfu', 'random']
                    )
                ];
                break;
                
            case 'queue':
                $config = [
                    'prefetch_count' => $this->askInput('Prefetch count', '10'),
                    'max_retries' => $this->askInput('Max retries', '3')
                ];
                break;
                
            case 'mail':
                $config = [
                    'smtp_host' => $this->askInput('SMTP host', 'smtp.gmail.com'),
                    'smtp_port' => $this->askInput('SMTP port', '587'),
                    'encryption' => $this->askChoice(
                        'Encryption:',
                        ['tls', 'ssl', 'none']
                    )
                ];
                break;
        }
        
        return $config;
    }

    private function configureMonitoring(): void {
        echo "\n=== Monitoring Configuration ===\n\n";
        
        $this->setupState['monitoring'] = [
            'enabled' => $this->askYesNo('Enable monitoring?'),
            'health_checks' => [
                'interval' => $this->askInput('Health check interval (seconds)', '30'),
                'timeout' => $this->askInput('Health check timeout (seconds)', '5')
            ],
            'metrics' => [
                'collection_interval' => $this->askInput('Metrics collection interval (seconds)', '60'),
                'retention_days' => $this->askInput('Metrics retention (days)', '30')
            ],
            'alerts' => [
                'enabled' => $this->askYesNo('Enable alerts?'),
                'channels' => $this->askMultiChoice(
                    'Alert channels:',
                    ['email', 'slack', 'webhook']
                )
            ],
            'logging' => [
                'level' => $this->askChoice(
                    'Log level:',
                    ['debug', 'info', 'warning', 'error']
                ),
                'retention_days' => $this->askInput('Log retention (days)', '30')
            ]
        ];
    }

    private function configureTesting(): void {
        echo "\n=== Testing Configuration ===\n\n";
        
        $this->setupState['testing'] = [
            'enabled' => $this->askYesNo('Enable automated testing?'),
            'suites' => [
                'unit' => $this->askYesNo('Run unit tests?'),
                'integration' => $this->askYesNo('Run integration tests?'),
                'e2e' => $this->askYesNo('Run end-to-end tests?')
            ],
            'coverage' => [
                'enabled' => $this->askYesNo('Enable code coverage?'),
                'threshold' => $this->askInput('Coverage threshold (%)', '80')
            ],
            'performance' => [
                'enabled' => $this->askYesNo('Enable performance testing?'),
                'threshold' => $this->askInput('Response time threshold (ms)', '1000')
            ]
        ];
    }

    private function saveConfiguration(): void {
        if ($this->askYesNo('Save configuration for future use?')) {
            $this->config->saveConfig($this->setupState);
            $this->logger->info('Configuration saved');
        }
    }

    private function runValidation(): void {
        echo "\n=== Validating Configuration ===\n\n";
        
        $validator = new Setup\Utils\Validator($this->setupState);
        $results = $validator->validate();
        
        if (!$results['valid']) {
            foreach ($results['errors'] as $error) {
                $this->logger->error($error);
            }
            throw new \Exception('Configuration validation failed');
        }
        
        $this->logger->info('Configuration validated successfully');
    }

    private function showSummary(): void {
        echo "\n=== Setup Summary ===\n\n";
        
        echo "Environment: {$this->setupState['environment']}\n";
        echo "Services: " . implode(', ', array_keys($this->setupState['services'] ?? [])) . "\n";
        echo "Monitoring: " . ($this->setupState['monitoring']['enabled'] ? 'Enabled' : 'Disabled') . "\n";
        echo "Testing: " . ($this->setupState['testing']['enabled'] ? 'Enabled' : 'Disabled') . "\n";
        
        if ($this->askYesNo('Proceed with setup?')) {
            $this->logger->info('Setup completed successfully');
        } else {
            $this->logger->info('Setup cancelled by user');
            exit(0);
        }
    }

    private function askInput(string $prompt, string $default = '', bool $secret = false): string {
        echo "{$prompt}";
        if ($default) {
            echo " [{$default}]";
        }
        echo ": ";
        
        if ($secret) {
            system('stty -echo');
            $input = trim(fgets(STDIN));
            system('stty echo');
            echo "\n";
        } else {
            $input = trim(fgets(STDIN));
        }
        
        return $input ?: $default;
    }

    private function askYesNo(string $prompt): bool {
        while (true) {
            $input = strtolower($this->askInput($prompt . ' (y/n)'));
            if (in_array($input, ['y', 'yes', 'n', 'no'])) {
                return in_array($input, ['y', 'yes']);
            }
            echo "Please answer 'y' or 'n'\n";
        }
    }

    private function askChoice(string $prompt, array $choices): string {
        echo "{$prompt}\n";
        foreach ($choices as $i => $choice) {
            echo ($i + 1) . ") {$choice}\n";
        }
        
        while (true) {
            $input = $this->askInput('Enter choice number');
            if (is_numeric($input) && isset($choices[$input - 1])) {
                return $choices[$input - 1];
            }
            echo "Invalid choice\n";
        }
    }

    private function askMultiChoice(string $prompt, array $choices): array {
        echo "{$prompt}\n";
        foreach ($choices as $i => $choice) {
            echo ($i + 1) . ") {$choice}\n";
        }
        
        $selected = [];
        while (true) {
            $input = $this->askInput('Enter choice number (or empty to finish)');
            if (empty($input)) {
                break;
            }
            if (is_numeric($input) && isset($choices[$input - 1])) {
                $selected[] = $choices[$input - 1];
            } else {
                echo "Invalid choice\n";
            }
        }
        
        return $selected;
    }

    private function showError(string $message): void {
        echo "\nError: {$message}\n";
        echo "Please check the logs for more details.\n";
    }
}

// Run setup
$setup = new Setup();
$setup->run(); 