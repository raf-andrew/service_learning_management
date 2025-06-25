<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class HealthMonitorCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for testing
        $healthDir = base_path('.health');
        if (!File::exists($healthDir)) {
            File::makeDirectory($healthDir, 0755, true);
        }
    }

    public function test_health_monitor_command_basic()
    {
        $this->artisan('health:monitor')
            ->expectsOutput('Starting health monitoring...')
            ->assertExitCode(0);
    }

    public function test_health_monitor_command_with_options()
    {
        $this->artisan('health:monitor', [
            '--services' => 'database,redis',
            '--interval' => '30'
        ])
            ->expectsOutput('Starting health monitoring...')
            ->assertExitCode(0);
    }

    public function test_health_monitor_command_with_invalid_interval()
    {
        $this->artisan('health:monitor', [
            '--interval' => '0'
        ])
            ->expectsOutput('Starting health monitoring...')
            ->assertExitCode(0);
    }

    public function test_health_monitor_command_with_verbose_option()
    {
        $this->artisan('health:monitor', [
            '--verbose' => true
        ])
            ->expectsOutput('Starting health monitoring...')
            ->assertExitCode(0);
    }

    public function test_health_monitor_command_creates_report_directory()
    {
        $this->artisan('health:monitor');
        
        $reportsDir = base_path('.health/reports');
        $this->assertTrue(File::exists($reportsDir));
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $healthDir = base_path('.health');
        if (File::exists($healthDir)) {
            File::deleteDirectory($healthDir);
        }
        
        parent::tearDown();
    }
} 