<?php

namespace Setup\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Setup\Setup;
use Setup\Utils\ConfigManager;
use Setup\Utils\DatabaseManager;
use Setup\Utils\Logger;
use Setup\Utils\ServiceManager;
use Setup\Utils\TestManager;

class SetupTest extends TestCase {
    private Setup $setup;
    private ConfigManager $configManager;
    private DatabaseManager $databaseManager;
    private Logger $logger;
    private ServiceManager $serviceManager;
    private TestManager $testManager;

    protected function setUp(): void {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->databaseManager = $this->createMock(DatabaseManager::class);
        $this->logger = $this->createMock(Logger::class);
        $this->serviceManager = $this->createMock(ServiceManager::class);
        $this->testManager = $this->createMock(TestManager::class);

        $this->setup = new Setup([
            'config_file' => 'config/test.php',
            'log_file' => 'logs/test.log',
            'log_level' => 'debug',
            'console_output' => false
        ]);
    }

    public function testSetupInitialization(): void {
        $this->assertInstanceOf(Setup::class, $this->setup);
    }

    public function testConfigLoading(): void {
        $this->configManager->expects($this->once())
            ->method('load')
            ->with('config/test.php');

        $this->setup->run();
    }

    public function testDatabaseSetup(): void {
        $this->databaseManager->expects($this->once())
            ->method('setup');

        $this->setup->run();
    }

    public function testServiceManagement(): void {
        $this->serviceManager->expects($this->once())
            ->method('startServices');

        $this->setup->run();
    }

    public function testTestExecution(): void {
        $this->testManager->expects($this->once())
            ->method('runTests');

        $this->setup->run();
    }

    public function testLogging(): void {
        $this->logger->expects($this->atLeastOnce())
            ->method('info')
            ->with($this->stringContains('Setup completed'));

        $this->setup->run();
    }
} 