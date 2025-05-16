<?php

namespace Tests\MCP\Infrastructure;

use Tests\MCP\BaseTestCase;
use App\MCP\Core\EnvironmentManager;
use App\MCP\Core\Config\Config;

class StagingEnvironmentTest extends BaseTestCase
{
    protected EnvironmentManager $envManager;
    protected Config $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envManager = new EnvironmentManager();
        $this->config = new Config();
    }

    public function test_staging_environment_is_properly_configured(): void
    {
        $this->envManager->setEnvironment('staging');
        
        // Verify staging-specific configurations
        $this->assertEquals('staging', $this->config->get('app.env'));
        $this->assertFalse($this->config->get('app.debug'));
        $this->assertTrue($this->config->get('app.mcp.enabled'));
        
        // Verify database configuration
        $this->assertNotEmpty($this->config->get('database.staging'));
        $this->assertNotEquals(
            $this->config->get('database.production'),
            $this->config->get('database.staging')
        );
        
        // Verify logging configuration
        $this->assertEquals('staging', $this->config->get('logging.channel'));
        $this->assertTrue($this->config->get('logging.staging_enabled'));
        
        // Verify security settings
        $this->assertTrue($this->config->get('security.staging_checks_enabled'));
        $this->assertTrue($this->config->get('security.audit_logging_enabled'));
    }

    public function test_staging_environment_has_proper_monitoring(): void
    {
        $this->envManager->setEnvironment('staging');
        
        // Verify monitoring configuration
        $this->assertTrue($this->config->get('monitoring.enabled'));
        $this->assertNotEmpty($this->config->get('monitoring.endpoints'));
        $this->assertTrue($this->config->get('monitoring.alerting_enabled'));
    }

    public function test_staging_environment_has_proper_backup_configuration(): void
    {
        $this->envManager->setEnvironment('staging');
        
        // Verify backup configuration
        $this->assertTrue($this->config->get('backup.enabled'));
        $this->assertNotEmpty($this->config->get('backup.schedule'));
        $this->assertNotEmpty($this->config->get('backup.retention_policy'));
    }

    public function test_staging_environment_has_proper_security_measures(): void
    {
        $this->envManager->setEnvironment('staging');
        
        // Verify security measures
        $this->assertTrue($this->config->get('security.rate_limiting_enabled'));
        $this->assertTrue($this->config->get('security.ssl_required'));
        $this->assertTrue($this->config->get('security.headers_enabled'));
    }
} 