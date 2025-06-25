<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CodespacesTestReporter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Mockery;

class CodespacesTestReporterTest extends TestCase
{
    protected $reporter;
    protected $reportPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporter = new CodespacesTestReporter();
        $this->reportPath = storage_path('app/codespaces/reports');
        
        if (!File::exists($this->reportPath)) {
            File::makeDirectory($this->reportPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->reportPath)) {
            File::deleteDirectory($this->reportPath);
        }
        parent::tearDown();
    }

    public function test_it_creates_report_directory_if_not_exists()
    {
        $this->assertDirectoryExists($this->reportPath);
    }

    public function test_it_starts_test_run()
    {
        $runId = $this->reporter->startTestRun();

        $this->assertNotEmpty($runId);
        $this->assertStringStartsWith('test-run-', $runId);
        $this->assertFileExists($this->reportPath . '/' . $runId . '.json');
    }

    public function test_it_records_test_success()
    {
        $runId = $this->reporter->startTestRun();
        
        $result = $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'passed',
            'duration' => 0.5,
            'memory' => '32MB'
        ]);

        $this->assertTrue($result);
        
        $report = json_decode(File::get($this->reportPath . '/' . $runId . '.json'), true);
        $this->assertCount(1, $report['results']);
        $this->assertEquals('Test Case 1', $report['results'][0]['name']);
        $this->assertEquals('passed', $report['results'][0]['status']);
    }

    public function test_it_records_test_failure()
    {
        $runId = $this->reporter->startTestRun();
        
        $result = $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'failed',
            'duration' => 0.5,
            'memory' => '32MB',
            'error' => 'Test failed: expected true but got false'
        ]);

        $this->assertTrue($result);
        
        $report = json_decode(File::get($this->reportPath . '/' . $runId . '.json'), true);
        $this->assertCount(1, $report['results']);
        $this->assertEquals('failed', $report['results'][0]['status']);
        $this->assertEquals('Test failed: expected true but got false', $report['results'][0]['error']);
    }

    public function test_it_generates_summary_report()
    {
        $runId = $this->reporter->startTestRun();
        
        $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'passed',
            'duration' => 0.5,
            'memory' => '32MB'
        ]);

        $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 2',
            'status' => 'failed',
            'duration' => 0.3,
            'memory' => '24MB',
            'error' => 'Test failed'
        ]);

        $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 3',
            'status' => 'skipped',
            'duration' => 0.1,
            'memory' => '16MB'
        ]);

        $summary = $this->reporter->generateSummary($runId);

        $this->assertEquals(2, $summary['passed']);
        $this->assertEquals(1, $summary['failed']);
        $this->assertEquals(1, $summary['skipped']);
        $this->assertEquals(0.9, $summary['duration']);
        $this->assertEquals('72MB', $summary['memory']);
        $this->assertNotEmpty($summary['timestamp']);
        $this->assertEquals('all', $summary['suite']);
        $this->assertCount(3, $summary['details']);
    }

    public function test_it_saves_summary_report()
    {
        $runId = $this->reporter->startTestRun();
        
        $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'passed',
            'duration' => 0.5,
            'memory' => '32MB'
        ]);

        $summary = $this->reporter->generateSummary($runId);
        $this->reporter->saveSummary($runId, $summary);

        $this->assertFileExists($this->reportPath . '/' . $runId . '-summary.json');
        
        $savedSummary = json_decode(File::get($this->reportPath . '/' . $runId . '-summary.json'), true);
        $this->assertEquals($summary, $savedSummary);
    }

    public function test_it_handles_test_timeout()
    {
        $runId = $this->reporter->startTestRun();
        
        $result = $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'timeout',
            'duration' => 30.0,
            'memory' => '128MB',
            'error' => 'Test timed out after 30 seconds'
        ]);

        $this->assertTrue($result);
        
        $report = json_decode(File::get($this->reportPath . '/' . $runId . '.json'), true);
        $this->assertEquals('timeout', $report['results'][0]['status']);
        $this->assertEquals('Test timed out after 30 seconds', $report['results'][0]['error']);
    }

    public function test_it_handles_test_error()
    {
        $runId = $this->reporter->startTestRun();
        
        $result = $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'error',
            'duration' => 0.1,
            'memory' => '16MB',
            'error' => 'PHP Fatal error: Uncaught Exception'
        ]);

        $this->assertTrue($result);
        
        $report = json_decode(File::get($this->reportPath . '/' . $runId . '.json'), true);
        $this->assertEquals('error', $report['results'][0]['status']);
        $this->assertEquals('PHP Fatal error: Uncaught Exception', $report['results'][0]['error']);
    }

    public function test_it_cleans_up_old_reports()
    {
        // Create some old reports
        $oldRunId = 'test-run-' . (time() - 86400); // 24 hours ago
        File::put($this->reportPath . '/' . $oldRunId . '.json', json_encode(['results' => []]));
        File::put($this->reportPath . '/' . $oldRunId . '-summary.json', json_encode(['summary' => []]));

        // Create a new report
        $newRunId = $this->reporter->startTestRun();
        $this->reporter->recordTestResult($newRunId, [
            'name' => 'Test Case 1',
            'status' => 'passed',
            'duration' => 0.5,
            'memory' => '32MB'
        ]);

        // Clean up old reports
        $this->reporter->cleanupOldReports(3600); // 1 hour threshold

        $this->assertFileDoesNotExist($this->reportPath . '/' . $oldRunId . '.json');
        $this->assertFileDoesNotExist($this->reportPath . '/' . $oldRunId . '-summary.json');
        $this->assertFileExists($this->reportPath . '/' . $newRunId . '.json');
    }

    public function test_it_returns_null_for_non_existent_test_run()
    {
        $result = $this->reporter->getTestResults('non-existent-run');
        $this->assertNull($result);
    }

    public function test_it_returns_test_results_for_existing_run()
    {
        $runId = $this->reporter->startTestRun();
        
        $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'passed',
            'duration' => 0.5,
            'memory' => '32MB'
        ]);

        $results = $this->reporter->getTestResults($runId);
        
        $this->assertNotNull($results);
        $this->assertCount(1, $results['results']);
        $this->assertEquals('Test Case 1', $results['results'][0]['name']);
    }

    public function test_it_handles_invalid_test_result_data()
    {
        $runId = $this->reporter->startTestRun();
        
        $result = $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'invalid_status',
            'duration' => 'invalid_duration',
            'memory' => 'invalid_memory'
        ]);

        $this->assertFalse($result);
    }

    public function test_it_handles_file_write_errors()
    {
        // Make the report directory read-only
        chmod($this->reportPath, 0444);

        $runId = $this->reporter->startTestRun();
        $result = $this->reporter->recordTestResult($runId, [
            'name' => 'Test Case 1',
            'status' => 'passed',
            'duration' => 0.5,
            'memory' => '32MB'
        ]);

        $this->assertFalse($result);

        // Restore directory permissions
        chmod($this->reportPath, 0755);
    }
} 