<?php

namespace Tests\Feature\Analysis;

use App\Analysis\TestReporter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/Analysis/TestReporterTest.php
 * @api-docs docs/api/analysis.yaml
 * @security-review docs/security/analysis.md
 * @qa-status Complete
 * @job-code ANA-001-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Analysis
 * @see \App\Analysis\TestReporter
 * 
 * Test suite for the TestReporter class.
 * Validates report generation in various formats and error handling.
 * 
 * @OpenAPI\Tag(name="Analysis Tests", description="Test reporter tests")
 */
class TestReporterTest extends TestCase
{
    /**
     * The test reporter instance.
     *
     * @var TestReporter
     */
    protected TestReporter $reporter;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->reporter = new TestReporter();
    }

    /**
     * Test that the reporter generates a valid JSON report.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test JSON report generation",
     *     description="Verifies that the reporter generates a valid JSON report"
     * )
     */
    public function it_generates_valid_json_report()
    {
        $testResults = [
            [
                'test_name' => 'Test 1',
                'status' => 'pass',
                'execution_time' => '0.5s',
            ],
        ];

        $report = $this->reporter->generateReport($testResults);
        $data = json_decode($report, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('format_version', $data);
        $this->assertArrayHasKey('test_results', $data);
        $this->assertEquals('1.0', $data['format_version']);
        $this->assertEquals($testResults, $data['test_results']);
    }

    /**
     * Test that the reporter generates a valid XML report.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test XML report generation",
     *     description="Verifies that the reporter generates a valid XML report"
     * )
     */
    public function it_generates_valid_xml_report()
    {
        $reporter = new TestReporter('xml');
        $testResults = [
            [
                'test_name' => 'Test 1',
                'status' => 'pass',
                'execution_time' => '0.5s',
            ],
        ];

        $report = $reporter->generateReport($testResults);
        $xml = simplexml_load_string($report);

        $this->assertNotFalse($xml);
        $this->assertEquals('1.0', (string) $xml['format_version']);
        $this->assertCount(1, $xml->test_results->test);
    }

    /**
     * Test that the reporter generates a valid HTML report.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test HTML report generation",
     *     description="Verifies that the reporter generates a valid HTML report"
     * )
     */
    public function it_generates_valid_html_report()
    {
        $reporter = new TestReporter('html');
        $testResults = [
            [
                'test_name' => 'Test 1',
                'status' => 'pass',
                'execution_time' => '0.5s',
            ],
        ];

        $report = $reporter->generateReport($testResults);

        $this->assertStringContainsString('<!DOCTYPE html>', $report);
        $this->assertStringContainsString('<title>Test Report</title>', $report);
        $this->assertStringContainsString('Test 1', $report);
        $this->assertStringContainsString('pass', $report);
    }

    /**
     * Test that the reporter generates a valid Markdown report.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test Markdown report generation",
     *     description="Verifies that the reporter generates a valid Markdown report"
     * )
     */
    public function it_generates_valid_markdown_report()
    {
        $reporter = new TestReporter('markdown');
        $testResults = [
            [
                'test_name' => 'Test 1',
                'status' => 'pass',
                'execution_time' => '0.5s',
            ],
        ];

        $report = $reporter->generateReport($testResults);

        $this->assertStringContainsString('# Test Report', $report);
        $this->assertStringContainsString('| Test Name | Status | Execution Time |', $report);
        $this->assertStringContainsString('| Test 1 | pass | 0.5s |', $report);
    }

    /**
     * Test that the reporter handles empty test results.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test empty results handling",
     *     description="Verifies that the reporter handles empty test results correctly"
     * )
     */
    public function it_handles_empty_test_results()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Test results cannot be empty');

        $this->reporter->generateReport([]);
    }

    /**
     * Test that the reporter handles invalid test results.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test invalid results handling",
     *     description="Verifies that the reporter handles invalid test results correctly"
     * )
     */
    public function it_handles_invalid_test_results()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required key: status');

        $this->reporter->generateReport([
            ['test_name' => 'Test 1'],
        ]);
    }

    /**
     * Test that the reporter handles invalid format.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test invalid format handling",
     *     description="Verifies that the reporter handles invalid format correctly"
     * )
     */
    public function it_handles_invalid_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported format: invalid');

        $reporter = new TestReporter('invalid');
        $reporter->generateReport([
            [
                'test_name' => 'Test 1',
                'status' => 'pass',
                'execution_time' => '0.5s',
            ],
        ]);
    }

    /**
     * Test that the reporter saves reports to the correct location.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test report saving",
     *     description="Verifies that the reporter saves reports to the correct location"
     * )
     */
    public function it_saves_reports_to_correct_location()
    {
        $outputPath = storage_path('reports');
        $reporter = new TestReporter('json', $outputPath);
        $testResults = [
            [
                'test_name' => 'Test 1',
                'status' => 'pass',
                'execution_time' => '0.5s',
            ],
        ];

        $report = $reporter->generateReport($testResults);
        $files = File::files($outputPath);

        $this->assertNotEmpty($files);
        $this->assertStringContainsString('.json', end($files)->getFilename());
    }

    /**
     * Test that the reporter logs report generation.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test report logging",
     *     description="Verifies that the reporter logs report generation"
     * )
     */
    public function it_logs_report_generation()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Test report generated'
                    && isset($context['path'])
                    && isset($context['format'])
                    && isset($context['size']);
            });

        $this->reporter->generateReport([
            [
                'test_name' => 'Test 1',
                'status' => 'pass',
                'execution_time' => '0.5s',
            ],
        ]);
    }
} 