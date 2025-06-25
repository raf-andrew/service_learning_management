<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class InfraTestFirewallRulesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure the reports directory exists
        File::makeDirectory(base_path('.web3/reports'), 0755, true, true);
    }

    protected function tearDown(): void
    {
        // Clean up the test report file
        $reportPath = base_path('.web3/reports/infrastructure_firewall_rules.md');
        if (File::exists($reportPath)) {
            File::delete($reportPath);
        }
        
        parent::tearDown();
    }

    public function test_it_generates_firewall_rules_report()
    {
        $this->artisan('infra:test-firewall-rules')
            ->expectsOutput('Firewall rules test (manual review) complete. Report written to ' . base_path('.web3/reports/infrastructure_firewall_rules.md'))
            ->assertExitCode(0);

        $reportPath = base_path('.web3/reports/infrastructure_firewall_rules.md');
        $this->assertTrue(File::exists($reportPath));
        
        $reportContent = File::get($reportPath);
        $this->assertStringContainsString('# Firewall Rules Test Report', $reportContent);
        $this->assertStringContainsString('## Results', $reportContent);
        $this->assertStringContainsString('Rules found: Manual review required', $reportContent);
        $this->assertStringContainsString('Open ports: Manual review required', $reportContent);
    }

    public function test_it_includes_timestamp_in_report()
    {
        $this->artisan('infra:test-firewall-rules')
            ->assertExitCode(0);

        $reportPath = base_path('.web3/reports/infrastructure_firewall_rules.md');
        $reportContent = File::get($reportPath);
        
        // Check that the report contains a timestamp
        $this->assertStringContainsString('_Last Run:', $reportContent);
        $this->assertStringContainsString(date('Y-m-d'), $reportContent);
    }

    public function test_it_handles_missing_reports_directory()
    {
        // Remove the reports directory if it exists
        $reportsDir = base_path('.web3/reports');
        if (File::exists($reportsDir)) {
            File::deleteDirectory($reportsDir);
        }

        $this->artisan('infra:test-firewall-rules')
            ->expectsOutput('Firewall rules test (manual review) complete. Report written to ' . base_path('.web3/reports/infrastructure_firewall_rules.md'))
            ->assertExitCode(0);

        $reportPath = base_path('.web3/reports/infrastructure_firewall_rules.md');
        $this->assertTrue(File::exists($reportPath));
    }
} 