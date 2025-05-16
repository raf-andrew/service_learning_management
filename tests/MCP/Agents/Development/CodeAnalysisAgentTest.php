<?php

namespace Tests\MCP\Agents\Development;

use Tests\MCP\BaseTestCase;
use App\MCP\Agents\Development\CodeAnalysisAgent;
use Illuminate\Support\Facades\File;

class CodeAnalysisAgentTest extends BaseTestCase
{
    protected CodeAnalysisAgent $agent;
    protected string $testPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new CodeAnalysisAgent();
        $this->testPath = storage_path('framework/testing/code_analysis');
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
            'analyze_code',
            'check_quality',
            'scan_security',
            'analyze_performance',
            'generate_documentation',
        ];

        foreach ($required as $capability) {
            $this->assertContains($capability, $capabilities);
        }
    }

    public function test_can_analyze_code(): void
    {
        $testFile = $this->createTestFile('test.php', '<?php echo "Hello World"; ?>');
        
        $result = $this->agent->performAction('analyze_code', ['path' => $this->testPath]);
        
        $this->assertArrayHasKey('files_analyzed', $result);
        $this->assertArrayHasKey('total_lines', $result);
        $this->assertArrayHasKey('code_complexity', $result);
        $this->assertArrayHasKey('dependencies', $result);
        $this->assertArrayHasKey('issues', $result);
        
        $this->assertEquals(1, $result['files_analyzed']);
        $this->assertEquals(1, $result['total_lines']);
    }

    public function test_can_check_quality(): void
    {
        $testFile = $this->createTestFile('test.php', '<?php echo "Test"; ?>');
        
        $result = $this->agent->performAction('check_quality', ['path' => $this->testPath]);
        
        $this->assertArrayHasKey('code_style', $result);
        $this->assertArrayHasKey('best_practices', $result);
        $this->assertArrayHasKey('documentation', $result);
        $this->assertArrayHasKey('test_coverage', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_can_scan_security(): void
    {
        $testFile = $this->createTestFile('test.php', '<?php echo $_GET["test"]; ?>');
        
        $result = $this->agent->performAction('scan_security', ['path' => $this->testPath]);
        
        $this->assertArrayHasKey('vulnerabilities', $result);
        $this->assertArrayHasKey('security_issues', $result);
        $this->assertArrayHasKey('dependency_audit', $result);
        $this->assertArrayHasKey('risk_assessment', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_can_analyze_performance(): void
    {
        $testFile = $this->createTestFile('test.php', '<?php for($i=0;$i<1000;$i++) echo $i; ?>');
        
        $result = $this->agent->performAction('analyze_performance', ['path' => $this->testPath]);
        
        $this->assertArrayHasKey('bottlenecks', $result);
        $this->assertArrayHasKey('resource_usage', $result);
        $this->assertArrayHasKey('optimization_opportunities', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_can_generate_documentation(): void
    {
        $testFile = $this->createTestFile('test.php', '<?php class Test { public function test() {} } ?>');
        
        $result = $this->agent->performAction('generate_documentation', [
            'path' => $this->testPath,
            'type' => 'api'
        ]);
        
        $this->assertArrayHasKey('documentation', $result);
        $this->assertArrayHasKey('coverage', $result);
        $this->assertArrayHasKey('examples', $result);
        $this->assertArrayHasKey('diagrams', $result);
    }

    public function test_throws_exception_for_missing_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('analyze_code', []);
    }

    public function test_throws_exception_for_missing_type_in_documentation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('generate_documentation', ['path' => $this->testPath]);
    }

    public function test_handles_empty_directory(): void
    {
        $result = $this->agent->performAction('analyze_code', ['path' => $this->testPath]);
        
        $this->assertEquals(0, $result['files_analyzed']);
        $this->assertEquals(0, $result['total_lines']);
        $this->assertEmpty($result['code_complexity']);
    }

    public function test_handles_multiple_file_types(): void
    {
        $this->createTestFile('test.php', '<?php echo "Test"; ?>');
        $this->createTestFile('test.js', 'console.log("Test");');
        $this->createTestFile('test.css', 'body { color: red; }');
        
        $result = $this->agent->performAction('analyze_code', ['path' => $this->testPath]);
        
        $this->assertEquals(3, $result['files_analyzed']);
    }

    protected function createTestFile(string $name, string $content): string
    {
        $path = $this->testPath . '/' . $name;
        File::put($path, $content);
        return $path;
    }
} 