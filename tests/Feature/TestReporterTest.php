<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\TestReporter;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Warning;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/TestReporterTest.php
 * @api-docs docs/api/test-reporter.yaml
 * @security-review docs/security/test-reporter.md
 * @qa-status Complete
 * @job-code TEST-001-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature
 * @see \Tests\TestReporter
 * 
 * Test suite for the TestReporter class.
 * Validates report generation in various formats and error handling.
 * 
 * @OpenAPI\Tag(name="Test Reporting Tests", description="Test reporter test suite")
 */
class TestReporterTest extends TestCase
{
    protected TestReporter $reporter;
    protected string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputPath = storage_path('reports/tests');
        $this->reporter = new TestReporter($this->outputPath);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->outputPath)) {
            File::deleteDirectory($this->outputPath);
        }
        parent::tearDown();
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test reporter initialization",
     *     description="Verifies that the reporter initializes correctly"
     * )
     */
    public function it_initializes_correctly(): void
    {
        $this->assertInstanceOf(TestReporter::class, $this->reporter);
        $this->assertDirectoryExists($this->outputPath);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test suite start handling",
     *     description="Verifies that the reporter handles test suite start correctly"
     * )
     */
    public function it_handles_test_suite_start(): void
    {
        $suite = $this->createMock(TestSuite::class);
        $suite->method('getName')->willReturn('TestSuite');

        $this->reporter->startTestSuite($suite);

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $report = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $this->assertEquals('TestSuite', $report['test_suite']);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test case handling",
     *     description="Verifies that the reporter handles test cases correctly"
     * )
     */
    public function it_handles_test_cases(): void
    {
        $test = $this->createMock(Test::class);
        $test->method('getName')->willReturn('testMethod');
        $test->method('getNumAssertions')->willReturn(5);

        $this->reporter->startTest($test);
        $this->reporter->endTest($test, 0.5);

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $report = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $this->assertCount(1, $report['tests']);
        $this->assertEquals('testMethod', $report['tests'][0]['name']);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test error handling",
     *     description="Verifies that the reporter handles test errors correctly"
     * )
     */
    public function it_handles_test_errors(): void
    {
        $test = $this->createMock(Test::class);
        $error = new \Exception('Test error');

        $this->reporter->startTest($test);
        $this->reporter->addError($test, $error, 0.5);

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $report = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $this->assertEquals('error', $report['tests'][0]['result']);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test failure handling",
     *     description="Verifies that the reporter handles test failures correctly"
     * )
     */
    public function it_handles_test_failures(): void
    {
        $test = $this->createMock(Test::class);
        $failure = new AssertionFailedError('Test failure');

        $this->reporter->startTest($test);
        $this->reporter->addFailure($test, $failure, 0.5);

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $report = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $this->assertEquals('failure', $report['tests'][0]['result']);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test warning handling",
     *     description="Verifies that the reporter handles test warnings correctly"
     * )
     */
    public function it_handles_test_warnings(): void
    {
        $test = $this->createMock(Test::class);
        $warning = new Warning('Test warning');

        $this->reporter->startTest($test);
        $this->reporter->addWarning($test, $warning, 0.5);

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $report = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $this->assertEquals('warning', $report['tests'][0]['result']);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test skipped handling",
     *     description="Verifies that the reporter handles skipped tests correctly"
     * )
     */
    public function it_handles_skipped_tests(): void
    {
        $test = $this->createMock(Test::class);
        $skipped = new \Exception('Test skipped');

        $this->reporter->startTest($test);
        $this->reporter->addSkippedTest($test, $skipped, 0.5);

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $report = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $this->assertEquals('skipped', $report['tests'][0]['result']);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test security check handling",
     *     description="Verifies that the reporter handles security checks correctly"
     * )
     */
    public function it_handles_security_checks(): void
    {
        $this->reporter->addSecurityCheck('XSS', 'passed');
        $this->reporter->addSecurityCheck('SQL Injection', 'failed');

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $report = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $this->assertCount(2, $report['security_checks']);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test code quality metrics",
     *     description="Verifies that the reporter handles code quality metrics correctly"
     * )
     */
    public function it_handles_code_quality_metrics(): void
    {
        $this->reporter->addCodeQualityMetric('complexity', 5);
        $this->reporter->addCodeQualityMetric('maintainability', 80);

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $report = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $this->assertCount(2, $report['code_quality']);
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test report formats",
     *     description="Verifies that the reporter generates reports in all supported formats"
     * )
     */
    public function it_generates_all_report_formats(): void
    {
        $test = $this->createMock(Test::class);
        $test->method('getName')->willReturn('testMethod');

        $this->reporter->startTest($test);
        $this->reporter->endTest($test, 0.5);

        $this->assertFileExists("{$this->outputPath}/test-report.json");
        $this->assertFileExists("{$this->outputPath}/test-report.xml");
        $this->assertFileExists("{$this->outputPath}/test-report.html");
        $this->assertFileExists("{$this->outputPath}/test-report.md");
    }

    /**
     * @test
     * @OpenAPI\Test(
     *     summary="Test report content",
     *     description="Verifies that the reporter generates correct report content"
     * )
     */
    public function it_generates_correct_report_content(): void
    {
        $test = $this->createMock(Test::class);
        $test->method('getName')->willReturn('testMethod');
        $test->method('getNumAssertions')->willReturn(5);

        $this->reporter->startTest($test);
        $this->reporter->endTest($test, 0.5);

        $jsonReport = json_decode(File::get("{$this->outputPath}/test-report.json"), true);
        $xmlReport = simplexml_load_string(File::get("{$this->outputPath}/test-report.xml"));
        $htmlReport = File::get("{$this->outputPath}/test-report.html");
        $markdownReport = File::get("{$this->outputPath}/test-report.md");

        $this->assertEquals('testMethod', $jsonReport['tests'][0]['name']);
        $this->assertEquals('testMethod', (string) $xmlReport->testcase[0]['name']);
        $this->assertStringContainsString('testMethod', $htmlReport);
        $this->assertStringContainsString('testMethod', $markdownReport);
    }
} 