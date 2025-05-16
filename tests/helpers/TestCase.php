<?php

declare(strict_types=1);

namespace MCP\Tests\Helpers;

use PHPUnit\Framework\TestCase as BaseTestCase;
use MCP\Core\Logger\Logger;
use MCP\Core\Database\ConnectionManager;

abstract class TestCase extends BaseTestCase
{
    protected static ?Logger $logger = null;
    protected static ?ConnectionManager $db = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        if (self::$logger === null) {
            self::$logger = new Logger('test', sys_get_temp_dir() . '/mcp_logs_test');
        }
        
        if (self::$db === null) {
            $config = require __DIR__ . '/../../config/test.php';
            self::$db = new ConnectionManager($config, self::$logger);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        if (self::$logger === null || self::$db === null) {
            self::setUpBeforeClass();
        }
    }

    protected function tearDown(): void
    {
        if (self::$db !== null) {
            self::$db->disconnect();
        }
        
        parent::tearDown();
    }

    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should contain key '{$key}'");
        }
    }

    protected function assertArrayHasValues(array $values, array $array, string $message = ''): void
    {
        foreach ($values as $value) {
            $this->assertContains($value, $array, $message ?: "Array should contain value '{$value}'");
        }
    }

    protected function getLogger(): Logger
    {
        return self::$logger;
    }

    protected function getDb(): ConnectionManager
    {
        return self::$db;
    }
} 