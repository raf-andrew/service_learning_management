<?php

namespace Tests\MCP;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Test case for the BaseTestCase class.
 * 
 * This test ensures that the BaseTestCase class correctly handles:
 * - Error logging
 * - Failure logging
 * - Coverage tracking
 * - Documentation tracking
 * 
 * @see docs/mcp/IMPLEMENTATION_SYSTEMATIC_CHECKLIST.md
 */
class BaseTestCaseTest extends TestCase
{
    private string $errorLogPath;
    private string $failureLogPath;
    private string $coverageLogPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->errorLogPath = __DIR__ . '/../../.errors/' . date('Y-m-d') . '.log';
        $this->failureLogPath = __DIR__ . '/../../.failures/' . date('Y-m-d') . '.log';
        $this->coverageLogPath = __DIR__ . '/../../.coverage/' . date('Y-m-d') . '.log';
        
        // Clear log files
        @unlink($this->errorLogPath);
        @unlink($this->failureLogPath);
        @unlink($this->coverageLogPath);
    }

    public function testErrorLogging(): void
    {
        $testCase = new class extends BaseTestCase {
            public function testError(): void
            {
                $this->logError('testError', 'Test error message');
            }
        };
        
        $testCase->testError();
        
        $this->assertFileExists($this->errorLogPath);
        $content = file_get_contents($this->errorLogPath);
        $this->assertStringContainsString('Test error message', $content);
    }

    public function testFailureLogging(): void
    {
        $testCase = new class extends BaseTestCase {
            public function testFailure(): void
            {
                $this->logFailure('testFailure', 'Test failure message');
            }
        };
        
        $testCase->testFailure();
        
        $this->assertFileExists($this->failureLogPath);
        $content = file_get_contents($this->failureLogPath);
        $this->assertStringContainsString('Test failure message', $content);
    }

    public function testCoverageLogging(): void
    {
        $testCase = new class extends BaseTestCase {
            public function testCoverage(): void
            {
                $this->testCoverage = ['line' => 1, 'branch' => 2];
                $this->logCoverage('testCoverage');
            }
        };
        
        $testCase->testCoverage();
        
        $this->assertFileExists($this->coverageLogPath);
        $content = file_get_contents($this->coverageLogPath);
        $this->assertStringContainsString('"line": 1', $content);
        $this->assertStringContainsString('"branch": 2', $content);
    }

    public function testDocumentationTracking(): void
    {
        $testCase = new class extends BaseTestCase {
            public function testDocumentation(): void
            {
                $this->setupDocumentation();
                $this->assertHasDocumentation();
            }
        };
        
        $testCase->testDocumentation();
    }

    public function testCoverageTracking(): void
    {
        $testCase = new class extends BaseTestCase {
            public function testCoverage(): void
            {
                $this->testCoverage = ['line' => 1];
                $this->assertHasCoverage();
            }
        };
        
        $testCase->testCoverage();
    }

    protected function tearDown(): void
    {
        // Clean up log files
        @unlink($this->errorLogPath);
        @unlink($this->failureLogPath);
        @unlink($this->coverageLogPath);
        
        parent::tearDown();
    }
} 