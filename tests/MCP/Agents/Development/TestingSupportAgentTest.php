<?php

namespace Tests\MCP\Agents\Development;

use Tests\MCP\BaseTestCase;
use App\MCP\Agents\Development\TestingSupportAgent;
use Illuminate\Support\Facades\File;

class TestingSupportAgentTest extends BaseTestCase
{
    protected TestingSupportAgent $agent;
    protected string $testPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new TestingSupportAgent();
        $this->testPath = storage_path('framework/testing/test_support');
        File::makeDirectory($this->testPath, 0755, true, true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->testPath)) {
            File::deleteDirectory($this->testPath);
        }
        parent::tearDown();
    }

    public function test_agent_has_correct_category(): void
    {
        $this->assertEquals('development', $this->agent->getCategory());
    }

    public function test_agent_has_required_capabilities(): void
    {
        $capabilities = $this->agent->getCapabilities();
        $required = [
            'generate_test_cases',
            'analyze_coverage',
            'monitor_test_execution',
            'analyze_test_results',
            'optimize_tests',
        ];

        foreach ($required as $capability) {
            $this->assertContains($capability, $capabilities);
        }
    }

    public function test_can_generate_test_cases(): void
    {
        $testFile = $this->createTestFile('TestClass.php', <<<'PHP'
<?php

namespace App\Test;

class TestClass
{
    public function testMethod()
    {
        return true;
    }
}
PHP
        );

        $result = $this->agent->performAction('generate_test_cases', [
            'path' => $this->testPath,
            'type' => 'unit'
        ]);

        $this->assertArrayHasKey('generated_tests', $result);
        $this->assertArrayHasKey('test_templates', $result);
        $this->assertArrayHasKey('coverage_analysis', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_can_analyze_coverage(): void
    {
        $testFile = $this->createTestFile('TestClass.php', '<?php class TestClass {}');
        
        $result = $this->agent->performAction('analyze_coverage', [
            'path' => $this->testPath
        ]);
        
        $this->assertArrayHasKey('total_coverage', $result);
        $this->assertArrayHasKey('uncovered_code', $result);
        $this->assertArrayHasKey('coverage_by_type', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_can_monitor_test_execution(): void
    {
        $result = $this->agent->performAction('monitor_test_execution', [
            'test_suite' => 'unit'
        ]);
        
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('performance', $result);
        $this->assertArrayHasKey('resources', $result);
        $this->assertArrayHasKey('issues', $result);
    }

    public function test_can_analyze_test_results(): void
    {
        $resultsFile = $this->createTestFile('test-results.xml', '<testsuites></testsuites>');
        
        $result = $this->agent->performAction('analyze_test_results', [
            'results_path' => $resultsFile
        ]);
        
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('failures', $result);
        $this->assertArrayHasKey('performance', $result);
        $this->assertArrayHasKey('trends', $result);
    }

    public function test_can_optimize_tests(): void
    {
        $testFile = $this->createTestFile('SlowTest.php', '<?php class SlowTest {}');
        
        $result = $this->agent->performAction('optimize_tests', [
            'path' => $this->testPath
        ]);
        
        $this->assertArrayHasKey('slow_tests', $result);
        $this->assertArrayHasKey('redundant_tests', $result);
        $this->assertArrayHasKey('optimization_opportunities', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_throws_exception_for_missing_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('generate_test_cases', ['type' => 'unit']);
    }

    public function test_throws_exception_for_missing_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('generate_test_cases', ['path' => $this->testPath]);
    }

    public function test_throws_exception_for_invalid_test_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('generate_test_cases', [
            'path' => $this->testPath,
            'type' => 'invalid'
        ]);
    }

    public function test_generates_test_with_correct_namespace(): void
    {
        $testFile = $this->createTestFile('TestClass.php', <<<'PHP'
<?php

namespace App\Test;

class TestClass
{
    public function testMethod()
    {
        return true;
    }
}
PHP
        );

        $result = $this->agent->performAction('generate_test_cases', [
            'path' => $this->testPath,
            'type' => 'unit'
        ]);

        $generatedTest = $result['generated_tests'][0] ?? null;
        $this->assertNotNull($generatedTest);
        $this->assertStringContainsString('namespace Tests\\Unit', File::get($generatedTest));
    }

    protected function createTestFile(string $name, string $content): string
    {
        $path = $this->testPath . '/' . $name;
        File::put($path, $content);
        return $path;
    }
} 