<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Configuration;
use Psr\Log\LoggerInterface;

class ConfigurationTest extends TestCase
{
    private Configuration $config;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = MockFactory::createMockLogger();
        $this->config = new Configuration($this->logger);
    }

    public function testConfigurationCanBeCreated(): void
    {
        $this->assertInstanceOf(Configuration::class, $this->config);
    }

    public function testConfigurationHasLogger(): void
    {
        $this->assertSame($this->logger, $this->config->getLogger());
    }

    public function testConfigurationCanSetAndGetValues(): void
    {
        $this->config->set('test.key', 'value');
        
        $this->assertEquals('value', $this->config->get('test.key'));
    }

    public function testConfigurationCanSetAndGetNestedValues(): void
    {
        $this->config->set('test.nested.key', 'value');
        
        $this->assertEquals('value', $this->config->get('test.nested.key'));
    }

    public function testConfigurationReturnsDefaultForMissingKeys(): void
    {
        $this->assertEquals('default', $this->config->get('nonexistent.key', 'default'));
    }

    public function testConfigurationCanCheckIfKeyExists(): void
    {
        $this->config->set('test.key', 'value');
        
        $this->assertTrue($this->config->has('test.key'));
        $this->assertFalse($this->config->has('nonexistent.key'));
    }

    public function testConfigurationCanLoadFromArray(): void
    {
        $data = [
            'test' => [
                'key' => 'value',
                'nested' => [
                    'key' => 'nested-value'
                ]
            ]
        ];
        
        $this->config->load($data);
        
        $this->assertEquals('value', $this->config->get('test.key'));
        $this->assertEquals('nested-value', $this->config->get('test.nested.key'));
    }

    public function testConfigurationCanLoadFromFile(): void
    {
        $file = __DIR__ . '/../../config/test-config.php';
        $this->config->loadFromFile($file);
        
        $this->assertEquals('test-value', $this->config->get('test.key'));
    }

    public function testConfigurationThrowsExceptionForInvalidFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->config->loadFromFile('nonexistent.php');
    }

    public function testConfigurationCanGetAllValues(): void
    {
        $data = [
            'test' => [
                'key' => 'value',
                'nested' => [
                    'key' => 'nested-value'
                ]
            ]
        ];
        
        $this->config->load($data);
        
        $all = $this->config->all();
        
        $this->assertIsArray($all);
        $this->assertEquals($data, $all);
    }

    public function testConfigurationCanClearValues(): void
    {
        $this->config->set('test.key', 'value');
        $this->config->clear();
        
        $this->assertFalse($this->config->has('test.key'));
    }
} 