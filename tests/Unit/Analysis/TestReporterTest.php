<?php

namespace Tests\Unit\Analysis;

use Tests\Unit\TestCase;
use App\Analysis\TestReporter;
use Illuminate\Support\Facades\File;

class TestReporterTest extends TestCase
{
    protected string $reportPath;
    protected TestReporter $reporter;
    protected string $checklistItem = 'TEST-001';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reportPath = storage_path('app/test-reports');
        $this->reporter = new TestReporter($this->checklistItem);
    }

    protected function tearDown(): void
    {
        // Clean up test reports
        if (File::exists($this->reportPath)) {
            File::deleteDirectory($this->reportPath);
        }
        
        parent::tearDown();
    }

    /**
     * Test that the reporter can be instantiated.
     *
     * @return void
     */
    public function test_it_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TestReporter::class, $this->reporter);
    }

    /**
     * Test that the reporter creates the report directory.
     *
     * @return void
     */
    public function test_it_creates_report_directory(): void
    {
        $this->assertDirectoryExists($this->reportPath);
    }

    /**
     * Test that the reporter can add test results.
     *
     * @return void
     */
    public function test_it_can_add_test_results(): void
    {
        $this->reporter->addResult('test_1', true, ['detail' => 'test passed']);
        $this->reporter->addResult('test_2', false, ['detail' => 'test failed']);
        
        $report = $this->reporter->generateReport();
        
        $this->assertCount(2, $report['results']);
        $this->assertTrue($report['results'][0]['passed']);
        $this->assertFalse($report['results'][1]['passed']);
    }

    /**
     * Test that the reporter can add metrics.
     *
     * @return void
     */
    public function test_it_can_add_metrics(): void
    {
        $this->reporter->addMetric('memory_usage', 1024);
        $this->reporter->addMetric('execution_time', 1.5);
        
        $report = $this->reporter->generateReport();
        
        $this->assertEquals(1024, $report['metrics']['memory_usage']);
        $this->assertEquals(1.5, $report['metrics']['execution_time']);
    }

    /**
     * Test that the reporter generates correct summary.
     *
     * @return void
     */
    public function test_it_generates_correct_summary(): void
    {
        $this->reporter->addResult('test_1', true);
        $this->reporter->addResult('test_2', true);
        $this->reporter->addResult('test_3', false);
        
        $report = $this->reporter->generateReport();
        
        $this->assertEquals(3, $report['summary']['total_tests']);
        $this->assertEquals(2, $report['summary']['passed_tests']);
        $this->assertEquals(1, $report['summary']['failed_tests']);
        $this->assertEquals(66.67, round($report['summary']['coverage_percentage'], 2));
    }

    /**
     * Test that the reporter saves reports to disk.
     *
     * @return void
     */
    public function test_it_saves_reports_to_disk(): void
    {
        $this->reporter->addResult('test_1', true);
        $report = $this->reporter->generateReport();
        
        $files = File::files($this->reportPath);
        $this->assertCount(1, $files);
        
        $savedReport = json_decode(File::get($files[0]), true);
        $this->assertEquals($report, $savedReport);
    }

    /**
     * Test that the reporter handles empty results.
     *
     * @return void
     */
    public function test_it_handles_empty_results(): void
    {
        $report = $this->reporter->generateReport();
        
        $this->assertEquals(0, $report['summary']['total_tests']);
        $this->assertEquals(0, $report['summary']['passed_tests']);
        $this->assertEquals(0, $report['summary']['failed_tests']);
        $this->assertEquals(0.0, $report['summary']['coverage_percentage']);
    }

    /**
     * Test that the reporter includes checklist item in report.
     *
     * @return void
     */
    public function test_it_includes_checklist_item(): void
    {
        $report = $this->reporter->generateReport();
        
        $this->assertEquals($this->checklistItem, $report['checklist_item']);
    }

    /**
     * Test that the reporter includes timestamp in report.
     *
     * @return void
     */
    public function test_it_includes_timestamp(): void
    {
        $report = $this->reporter->generateReport();
        
        $this->assertIsString($report['timestamp']);
        $this->assertNotEmpty($report['timestamp']);
    }
} 