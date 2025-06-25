<?php

namespace Tests\Feature\Sniffing;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Models\Sniffing\SniffResult;
use App\Models\Sniffing\SniffViolation;

class SniffingSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing data
        SniffViolation::truncate();
        SniffResult::truncate();
    }

    public function test_can_initialize_sniffing_system()
    {
        $this->artisan('sniffing:init')
            ->expectsOutput('Initializing sniffing system...')
            ->expectsOutput('Sniffing system initialized successfully!')
            ->assertExitCode(0);

        $this->assertDirectoryExists('.sniffing');
        $this->assertDirectoryExists('.sniffing/rules');
        $this->assertDirectoryExists('.sniffing/reports');
        $this->assertFileExists('.sniffing/phpcs.xml');
    }

    public function test_can_run_sniffing()
    {
        $this->artisan('sniffing:init');
        
        $this->artisan('sniff:run', [
            '--report' => 'xml',
            '--file' => 'app/Console/Commands/SniffCommand.php'
        ])->assertExitCode(0);

        $this->assertDatabaseHas('sniff_results', [
            'file_path' => 'app/Console/Commands/SniffCommand.php',
            'report_format' => 'xml'
        ]);
    }

    public function test_can_generate_report()
    {
        $this->artisan('sniffing:init');
        $this->artisan('sniff:run', ['--report' => 'xml']);

        $this->artisan('sniff:generate-report', [
            '--format' => 'html',
            '--output' => 'reports/test_report.html'
        ])->assertExitCode(0);

        $this->assertFileExists('reports/test_report.html');
    }

    public function test_can_manage_rules()
    {
        $this->artisan('sniffing:init');

        // Add a new rule
        $this->artisan('sniffing:rules', [
            'action' => 'add',
            '--type' => 'security',
            '--name' => 'NoDebugCode',
            '--description' => 'Prevent debug code in production',
            '--code' => 'DebugCodeSniff',
            '--severity' => 'error'
        ])->assertExitCode(0);

        $this->assertFileExists('.sniffing/rules/ServiceLearning/NoDebugCodeSniff.php');

        // List rules
        $this->artisan('sniffing:rules', [
            'action' => 'list'
        ])->assertExitCode(0);

        // Remove rule
        $this->artisan('sniffing:rules', [
            'action' => 'remove',
            '--name' => 'NoDebugCode'
        ])->assertExitCode(0);

        $this->assertFileDoesNotExist('.sniffing/rules/ServiceLearning/NoDebugCodeSniff.php');
    }

    public function test_can_analyze_results()
    {
        $this->artisan('sniffing:init');
        $this->artisan('sniff:run', ['--report' => 'xml']);

        $this->artisan('sniffing:analyze', [
            '--days' => '7',
            '--format' => 'html',
            '--output' => 'reports/analysis.html',
            '--trends' => true,
            '--files' => true,
            '--rules' => true
        ])->assertExitCode(0);

        $this->assertFileExists('reports/analysis.html');
    }

    public function test_can_clear_sniffing_data()
    {
        $this->artisan('sniffing:init');
        $this->artisan('sniff:run', ['--report' => 'xml']);

        $this->assertDatabaseHas('sniff_results', []);

        $this->artisan('sniff:clear', ['--all' => true])
            ->expectsOutput('Cleared all sniffing results')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('sniff_results', []);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists('reports/test_report.html')) {
            File::delete('reports/test_report.html');
        }
        if (File::exists('reports/analysis.html')) {
            File::delete('reports/analysis.html');
        }

        parent::tearDown();
    }
} 