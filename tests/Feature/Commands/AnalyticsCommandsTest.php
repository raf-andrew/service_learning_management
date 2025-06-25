<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class AnalyticsCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary directories for testing
        $analyticsDir = base_path('.analytics');
        if (!File::exists($analyticsDir)) {
            File::makeDirectory($analyticsDir, 0755, true);
        }
    }

    public function test_analytics_aggregate_command()
    {
        $this->artisan('analytics:aggregate')
            ->expectsOutput('Starting data aggregation...')
            ->assertExitCode(0);
    }

    public function test_analytics_aggregate_command_with_type()
    {
        $this->artisan('analytics:aggregate', [
            '--type' => 'contract'
        ])
            ->expectsOutput('Starting data aggregation...')
            ->assertExitCode(0);
    }

    public function test_analytics_aggregate_command_with_date_range()
    {
        $this->artisan('analytics:aggregate', [
            '--start' => '2024-01-01',
            '--end' => '2024-01-31'
        ])
            ->expectsOutput('Starting data aggregation...')
            ->assertExitCode(0);
    }

    public function test_analytics_alert_command_create()
    {
        $this->artisan('analytics:alert', [
            'action' => 'create',
            '--name' => 'test-alert',
            '--type' => 'threshold',
            '--metric' => 'transaction_count',
            '--condition' => 'gt',
            '--value' => '100'
        ])
            ->expectsOutput('Processing alert action...')
            ->assertExitCode(0);
    }

    public function test_analytics_alert_command_list()
    {
        $this->artisan('analytics:alert', [
            'action' => 'list'
        ])
            ->expectsOutput('Processing alert action...')
            ->assertExitCode(0);
    }

    public function test_analytics_backup_command_create()
    {
        $this->artisan('analytics:backup', [
            'action' => 'create',
            '--type' => 'all'
        ])
            ->expectsOutput('Processing backup action...')
            ->assertExitCode(0);
    }

    public function test_analytics_backup_command_list()
    {
        $this->artisan('analytics:backup', [
            'action' => 'list'
        ])
            ->expectsOutput('Processing backup action...')
            ->assertExitCode(0);
    }

    public function test_analytics_collect_command()
    {
        $this->artisan('analytics:collect')
            ->expectsOutput('Starting data collection...')
            ->assertExitCode(0);
    }

    public function test_analytics_collect_command_with_network()
    {
        $this->artisan('analytics:collect', [
            '--network' => 'testnet'
        ])
            ->expectsOutput('Starting data collection...')
            ->assertExitCode(0);
    }

    public function test_analytics_export_command()
    {
        $this->artisan('analytics:export')
            ->expectsOutput('Starting data export...')
            ->assertExitCode(0);
    }

    public function test_analytics_export_command_with_format()
    {
        $this->artisan('analytics:export', [
            '--format' => 'csv'
        ])
            ->expectsOutput('Starting data export...')
            ->assertExitCode(0);
    }

    public function test_analytics_validate_command()
    {
        $this->artisan('analytics:validate')
            ->expectsOutput('Starting data validation...')
            ->assertExitCode(0);
    }

    public function test_analytics_validate_command_with_threshold()
    {
        $this->artisan('analytics:validate', [
            '--threshold' => '95'
        ])
            ->expectsOutput('Starting data validation...')
            ->assertExitCode(0);
    }

    public function test_analytics_visualize_command()
    {
        $this->artisan('analytics:visualize', [
            'type' => 'dashboard'
        ])
            ->expectsOutput('Generating visualization...')
            ->assertExitCode(0);
    }

    public function test_analytics_visualize_command_with_format()
    {
        $this->artisan('analytics:visualize', [
            'type' => 'chart',
            '--format' => 'pdf'
        ])
            ->expectsOutput('Generating visualization...')
            ->assertExitCode(0);
    }

    public function test_analytics_web3_command()
    {
        $this->artisan('analytics:web3', [
            'action' => 'collect'
        ])
            ->expectsOutput('Processing Web3 analytics...')
            ->assertExitCode(0);
    }

    public function test_analytics_web3_command_with_type()
    {
        $this->artisan('analytics:web3', [
            'action' => 'analyze',
            '--type' => 'contract'
        ])
            ->expectsOutput('Processing Web3 analytics...')
            ->assertExitCode(0);
    }

    public function test_analytics_commands_create_reports()
    {
        $this->artisan('analytics:aggregate');
        
        $reportsDir = base_path('.analytics/reports');
        $this->assertTrue(File::exists($reportsDir));
    }

    protected function tearDown(): void
    {
        // Clean up test directories
        $analyticsDir = base_path('.analytics');
        if (File::exists($analyticsDir)) {
            File::deleteDirectory($analyticsDir);
        }
        
        parent::tearDown();
    }
} 