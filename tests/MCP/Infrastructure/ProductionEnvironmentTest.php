<?php

namespace Tests\MCP\Infrastructure;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\EnvironmentManager;
use Illuminate\Support\Facades\Config;

class ProductionEnvironmentTest extends BaseTestCase
{
    protected EnvironmentManager $envManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envManager = new EnvironmentManager();
        $this->envManager->setEnvironment('production');
    }

    public function test_production_environment_configuration_is_valid(): void
    {
        $config = Config::get('mcp');
        
        // Test app configuration
        $this->assertEquals('production', $config['app']['env']);
        $this->assertFalse($config['app']['debug']);
        $this->assertFalse($config['app']['mcp']['enabled']);
        
        // Test database configuration
        $this->assertArrayHasKey('production', $config['database']);
        $this->assertTrue($config['database']['production']['strict']);
        $this->assertArrayHasKey('options', $config['database']['production']);
        
        // Test logging configuration
        $this->assertEquals('production', $config['logging']['channel']);
        $this->assertTrue($config['logging']['production_enabled']);
        $this->assertArrayHasKey('papertrail', $config['logging']['channels']);
        
        // Test monitoring configuration
        $this->assertTrue($config['monitoring']['enabled']);
        $this->assertTrue($config['monitoring']['alerting_enabled']);
        $this->assertArrayHasKey('pagerduty', $config['monitoring']['alert_channels']);
        
        // Test backup configuration
        $this->assertTrue($config['backup']['enabled']);
        $this->assertArrayHasKey('verification', $config['backup']);
        $this->assertTrue($config['backup']['verification']['enabled']);
        
        // Test security configuration
        $this->assertTrue($config['security']['production_checks_enabled']);
        $this->assertTrue($config['security']['ssl_required']);
        $this->assertEquals('DENY', $config['security']['headers']['X-Frame-Options']);
        $this->assertArrayHasKey('Strict-Transport-Security', $config['security']['headers']);
        
        // Test cache configuration
        $this->assertEquals('redis', $config['cache']['driver']);
        $this->assertEquals('production', $config['cache']['connection']);
        
        // Test queue configuration
        $this->assertEquals('redis', $config['queue']['driver']);
        $this->assertEquals('production', $config['queue']['connection']);
    }

    public function test_production_environment_has_strict_security_headers(): void
    {
        $config = Config::get('mcp');
        $headers = $config['security']['headers'];
        
        $this->assertEquals('DENY', $headers['X-Frame-Options']);
        $this->assertEquals('1; mode=block', $headers['X-XSS-Protection']);
        $this->assertEquals('nosniff', $headers['X-Content-Type-Options']);
        $this->assertEquals('strict-origin-when-cross-origin', $headers['Referrer-Policy']);
        $this->assertStringContainsString("default-src 'self'", $headers['Content-Security-Policy']);
        $this->assertStringContainsString('max-age=31536000', $headers['Strict-Transport-Security']);
        $this->assertEquals('none', $headers['X-Permitted-Cross-Domain-Policies']);
        $this->assertEquals('noopen', $headers['X-Download-Options']);
        $this->assertEquals('off', $headers['X-DNS-Prefetch-Control']);
    }

    public function test_production_environment_has_secure_session_settings(): void
    {
        $config = Config::get('mcp');
        $session = $config['security']['session'];
        
        $this->assertTrue($session['secure']);
        $this->assertTrue($session['httponly']);
        $this->assertEquals('strict', $session['samesite']);
        $this->assertTrue($session['expire_on_close']);
    }

    public function test_production_environment_has_secure_cookie_settings(): void
    {
        $config = Config::get('mcp');
        $cookies = $config['security']['cookies'];
        
        $this->assertTrue($cookies['secure']);
        $this->assertTrue($cookies['httponly']);
        $this->assertEquals('strict', $cookies['samesite']);
    }

    public function test_production_environment_has_comprehensive_monitoring(): void
    {
        $config = Config::get('mcp');
        $monitoring = $config['monitoring'];
        
        $this->assertTrue($monitoring['enabled']);
        $this->assertTrue($monitoring['alerting_enabled']);
        $this->assertArrayHasKey('email', $monitoring['alert_channels']);
        $this->assertArrayHasKey('slack', $monitoring['alert_channels']);
        $this->assertArrayHasKey('pagerduty', $monitoring['alert_channels']);
        $this->assertTrue($monitoring['metrics']['enabled']);
        $this->assertEquals('prometheus', $monitoring['metrics']['driver']);
    }

    public function test_production_environment_has_robust_backup_configuration(): void
    {
        $config = Config::get('mcp');
        $backup = $config['backup'];
        
        $this->assertTrue($backup['enabled']);
        $this->assertEquals('0 0 * * *', $backup['schedule']);
        $this->assertArrayHasKey('verification', $backup);
        $this->assertTrue($backup['verification']['enabled']);
        $this->assertEquals('0 6 * * *', $backup['verification']['schedule']);
        $this->assertTrue($backup['verification']['notify_on_failure']);
        $this->assertEquals('AES256', $backup['storage']['encryption']);
    }
} 