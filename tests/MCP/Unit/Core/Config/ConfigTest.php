<?php

declare(strict_types=1);

namespace MCP\Tests\Unit\Core\Config;

use MCP\Core\Config\Config;
use MCP\Core\Logger\Logger;
use MCP\Tests\Helpers\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigTest extends TestCase
{
    private Config $config;
    private Logger|MockObject $logger;
    private string $configPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(Logger::class);
        $this->configPath = sys_get_temp_dir() . '/mcp_test_config';
        
        if (!is_dir($this->configPath)) {
            mkdir($this->configPath, 0755, true);
        }

        $this->config = new Config($this->configPath, $this->logger);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->configPath)) {
            array_map('unlink', glob($this->configPath . '/*'));
            rmdir($this->configPath);
        }

        parent::tearDown();
    }

    public function testConfigCanBeCreated(): void
    {
        $this->assertInstanceOf(Config::class, $this->config);
    }

    public function testConfigCanGetValue(): void
    {
        $this->config->set('test.key', 'value');
        $this->assertEquals('value', $this->config->get('test.key'));
    }

    public function testConfigReturnsDefaultForNonExistentKey(): void
    {
        $this->assertEquals('default', $this->config->get('non.existent', 'default'));
    }

    public function testConfigCanSetValue(): void
    {
        $this->config->set('test.key', 'value');
        $this->assertEquals('value', $this->config->get('test.key'));
    }

    public function testConfigCanSetNestedValue(): void
    {
        $this->config->set('test.nested.key', 'value');
        $this->assertEquals('value', $this->config->get('test.nested.key'));
    }

    public function testConfigCanCheckIfKeyExists(): void
    {
        $this->config->set('test.key', 'value');
        $this->assertTrue($this->config->has('test.key'));
        $this->assertFalse($this->config->has('non.existent'));
    }

    public function testConfigCanGetAllValues(): void
    {
        $this->config->set('test.key1', 'value1');
        $this->config->set('test.key2', 'value2');

        $all = $this->config->all();
        $this->assertIsArray($all);
        $this->assertEquals('value1', $all['test']['key1']);
        $this->assertEquals('value2', $all['test']['key2']);
    }

    public function testConfigCanLoadValues(): void
    {
        $values = [
            'test' => [
                'key1' => 'value1',
                'key2' => 'value2'
            ]
        ];

        $this->config->load($values);
        $this->assertEquals('value1', $this->config->get('test.key1'));
        $this->assertEquals('value2', $this->config->get('test.key2'));
    }

    public function testConfigCanSaveToFile(): void
    {
        $this->config->set('test.key', 'value');
        
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Configuration file saved', [
                'file' => $this->configPath . '/config.php'
            ]);

        $this->config->save();

        $this->assertFileExists($this->configPath . '/config.php');
        $savedConfig = require $this->configPath . '/config.php';
        $this->assertEquals('value', $savedConfig['test']['key']);
    }

    public function testConfigThrowsExceptionWhenSavingFails(): void
    {
        $this->config->set('test.key', 'value');
        
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Failed to save configuration file', [
                'file' => $this->configPath . '/config.php'
            ]);

        chmod($this->configPath, 0444);
        $this->expectException(\RuntimeException::class);
        $this->config->save();
    }

    public function testConfigLoadsFromFile(): void
    {
        $configFile = $this->configPath . '/config.php';
        $content = "<?php\n\nreturn ['test' => ['key' => 'value']];\n";
        file_put_contents($configFile, $content);

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Configuration loaded', [
                'file' => $configFile
            ]);

        $config = new Config($this->configPath, $this->logger);
        $this->assertEquals('value', $config->get('test.key'));
    }

    public function testConfigHandlesMissingFile(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Configuration file not found', [
                'file' => $this->configPath . '/config.php'
            ]);

        $config = new Config($this->configPath, $this->logger);
        $this->assertEmpty($config->all());
    }

    public function testConfigThrowsExceptionForInvalidFile(): void
    {
        $configFile = $this->configPath . '/config.php';
        $content = "<?php\n\nreturn 'invalid';\n";
        file_put_contents($configFile, $content);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Invalid configuration file format', [
                'file' => $configFile
            ]);

        $this->expectException(\RuntimeException::class);
        new Config($this->configPath, $this->logger);
    }
} 