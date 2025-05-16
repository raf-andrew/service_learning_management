<?php

namespace Setup;

use Setup\Utils\ConfigManager;
use Setup\Utils\DatabaseManager;
use Setup\Utils\Logger;
use Setup\Utils\ServiceManager;
use Setup\Utils\TestManager;

class Setup {
    private array $options;
    private Logger $logger;
    private ConfigManager $configManager;
    private DatabaseManager $databaseManager;
    private ServiceManager $serviceManager;
    private TestManager $testManager;

    public function __construct(array $options = []) {
        $this->options = array_merge([
            'config_file' => null,
            'log_file' => null,
            'log_level' => 'info',
            'console_output' => true
        ], $options);

        $this->initialize();
    }

    private function initialize(): void {
        // Initialize logger first
        $this->logger = new Logger(
            $this->options['log_file'],
            $this->options['log_level'],
            $this->options['console_output']
        );

        $this->logger->info('Initializing setup');

        // Initialize config manager
        $this->configManager = new ConfigManager(
            $this->options['config_file'] ?? dirname(__DIR__) . '/config/config.php',
            $this->logger
        );

        // Load configuration
        $this->configManager->load();

        // Validate configuration
        if (!$this->configManager->validate()) {
            throw new \RuntimeException('Invalid configuration');
        }

        // Initialize other managers
        $this->databaseManager = new DatabaseManager(
            $this->configManager->get('database'),
            $this->logger
        );

        $this->serviceManager = new ServiceManager(
            $this->configManager->get('services'),
            $this->logger
        );

        $this->testManager = new TestManager(
            $this->configManager->get('testing'),
            $this->logger
        );

        $this->logger->info('Setup initialized');
    }

    public function run(): void {
        try {
            $this->logger->info('Starting setup process');

            // Setup database
            $this->databaseManager->connect();
            $this->databaseManager->setup();

            // Start services
            $this->serviceManager->startServices();

            // Run tests
            $this->testManager->runTests();

            $this->logger->info('Setup completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('Setup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function cleanup(): void {
        try {
            $this->logger->info('Starting cleanup');

            // Stop services
            $this->serviceManager->stopServices();

            $this->logger->info('Cleanup completed');
        } catch (\Exception $e) {
            $this->logger->error('Cleanup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getConfig(): array {
        return $this->configManager->all();
    }

    public function getLogger(): Logger {
        return $this->logger;
    }

    public function getDatabaseManager(): DatabaseManager {
        return $this->databaseManager;
    }

    public function getServiceManager(): ServiceManager {
        return $this->serviceManager;
    }

    public function getTestManager(): TestManager {
        return $this->testManager;
    }
} 