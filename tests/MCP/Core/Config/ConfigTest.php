<?php

namespace Tests\MCP\Core\Config;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\Config\Config;

class ConfigTest extends BaseTestCase
{
    protected Config $config;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new Config();
    }
    
    public function test_can_get_and_set_config_values(): void
    {
        $this->config->set('app.name', 'MCP');
        $this->assertEquals('MCP', $this->config->get('app.name'));
        
        $this->config->set('app.debug', true);
        $this->assertTrue($this->config->get('app.debug'));
        
        $this->config->set('app.version', '1.0.0');
        $this->assertEquals('1.0.0', $this->config->get('app.version'));
    }
    
    public function test_can_set_multiple_values_at_once(): void
    {
        $this->config->set([
            'app.name' => 'MCP',
            'app.debug' => true,
            'app.version' => '1.0.0'
        ]);
        
        $this->assertEquals('MCP', $this->config->get('app.name'));
        $this->assertTrue($this->config->get('app.debug'));
        $this->assertEquals('1.0.0', $this->config->get('app.version'));
    }
    
    public function test_returns_default_value_when_key_does_not_exist(): void
    {
        $this->assertNull($this->config->get('nonexistent.key'));
        $this->assertEquals('default', $this->config->get('nonexistent.key', 'default'));
    }
    
    public function test_can_check_if_key_exists(): void
    {
        $this->config->set('app.name', 'MCP');
        
        $this->assertTrue($this->config->has('app.name'));
        $this->assertFalse($this->config->has('nonexistent.key'));
    }
    
    public function test_can_get_all_config_values(): void
    {
        $values = [
            'app.name' => 'MCP',
            'app.debug' => true,
            'app.version' => '1.0.0'
        ];
        
        $this->config->set($values);
        
        $this->assertEquals($values, $this->config->all());
    }
    
    public function test_can_handle_nested_arrays(): void
    {
        $this->config->set('app', [
            'name' => 'MCP',
            'debug' => true,
            'version' => '1.0.0',
            'settings' => [
                'timezone' => 'UTC',
                'locale' => 'en'
            ]
        ]);
        
        $this->assertEquals('MCP', $this->config->get('app.name'));
        $this->assertTrue($this->config->get('app.debug'));
        $this->assertEquals('1.0.0', $this->config->get('app.version'));
        $this->assertEquals('UTC', $this->config->get('app.settings.timezone'));
        $this->assertEquals('en', $this->config->get('app.settings.locale'));
    }
} 