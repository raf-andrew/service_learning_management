<?php

namespace Tests\MCP\Agents\Development;

use Tests\MCP\BaseTestCase;
use App\MCP\Agents\Development\DocumentationAgent;
use Illuminate\Support\Facades\File;

class DocumentationAgentTest extends BaseTestCase
{
    protected DocumentationAgent $agent;
    protected string $testPath;
    protected string $templatesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new DocumentationAgent();
        $this->testPath = storage_path('framework/testing/documentation');
        $this->templatesPath = $this->testPath . '/templates';
        
        File::makeDirectory($this->testPath, 0755, true, true);
        File::makeDirectory($this->templatesPath, 0755, true, true);
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
            'generate_documentation',
            'update_documentation',
            'validate_documentation',
            'analyze_documentation',
            'generate_examples',
        ];

        foreach ($required as $capability) {
            $this->assertContains($capability, $capabilities);
        }
    }

    public function test_can_generate_documentation(): void
    {
        $this->createTestFile('Component.php', '<?php class Component { }');
        
        $result = $this->agent->performAction('generate_documentation', [
            'type' => 'component',
            'path' => $this->testPath . '/Component.php'
        ]);
        
        $this->assertArrayHasKey('generated_file', $result);
        $this->assertArrayHasKey('sections', $result);
        $this->assertArrayHasKey('content_analysis', $result);
        $this->assertArrayHasKey('recommendations', $result);
        
        $this->assertTrue(File::exists($result['generated_file']));
    }

    public function test_can_update_documentation(): void
    {
        $docFile = $this->createTestFile('api-docs.md', "# API Documentation\n\n## Overview\n\nOld content");
        
        $result = $this->agent->performAction('update_documentation', [
            'path' => $docFile,
            'type' => 'api',
            'sections' => ['Overview' => 'New content']
        ]);
        
        $this->assertArrayHasKey('updated_file', $result);
        $this->assertArrayHasKey('changes', $result);
        $this->assertArrayHasKey('validation', $result);
        $this->assertArrayHasKey('recommendations', $result);
        
        $this->assertStringContainsString('New content', File::get($docFile));
    }

    public function test_can_validate_documentation(): void
    {
        $docFile = $this->createTestFile('api-docs.md', "# API Documentation\n\n## Overview\n\nContent");
        
        $result = $this->agent->performAction('validate_documentation', [
            'path' => $docFile
        ]);
        
        $this->assertArrayHasKey('structure', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('links', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_can_analyze_documentation(): void
    {
        $docFile = $this->createTestFile('api-docs.md', "# API Documentation\n\n## Overview\n\nContent");
        
        $result = $this->agent->performAction('analyze_documentation', [
            'path' => $docFile
        ]);
        
        $this->assertArrayHasKey('coverage', $result);
        $this->assertArrayHasKey('quality', $result);
        $this->assertArrayHasKey('completeness', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_can_generate_examples(): void
    {
        $this->createTestFile('Component.php', '<?php class Component { public function test() {} }');
        
        $result = $this->agent->performAction('generate_examples', [
            'path' => $this->testPath . '/Component.php',
            'type' => 'component'
        ]);
        
        $this->assertArrayHasKey('examples', $result);
        $this->assertArrayHasKey('test_cases', $result);
        $this->assertArrayHasKey('usage_scenarios', $result);
        $this->assertArrayHasKey('recommendations', $result);
    }

    public function test_throws_exception_for_missing_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('generate_documentation', [
            'path' => $this->testPath . '/test.php'
        ]);
    }

    public function test_throws_exception_for_missing_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('generate_documentation', [
            'type' => 'api'
        ]);
    }

    public function test_throws_exception_for_invalid_doc_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->agent->performAction('generate_documentation', [
            'type' => 'invalid',
            'path' => $this->testPath . '/test.php'
        ]);
    }

    public function test_generates_table_of_contents(): void
    {
        $content = <<<'MARKDOWN'
# Main Title

## Section 1

Content 1

## Section 2

Content 2
MARKDOWN;

        $docFile = $this->createTestFile('doc.md', $content);
        
        $result = $this->agent->performAction('generate_documentation', [
            'type' => 'api',
            'path' => $docFile
        ]);
        
        $generatedContent = File::get($result['generated_file']);
        $this->assertStringContainsString('# Table of Contents', $generatedContent);
        $this->assertStringContainsString('- [Section 1](#section-1)', $generatedContent);
        $this->assertStringContainsString('- [Section 2](#section-2)', $generatedContent);
    }

    public function test_includes_timestamp_when_configured(): void
    {
        $this->createTestFile('Component.php', '<?php class Component { }');
        
        $result = $this->agent->performAction('generate_documentation', [
            'type' => 'component',
            'path' => $this->testPath . '/Component.php'
        ]);
        
        $generatedContent = File::get($result['generated_file']);
        $this->assertStringContainsString('Last updated:', $generatedContent);
    }

    protected function createTestFile(string $name, string $content): string
    {
        $path = $this->testPath . '/' . $name;
        File::put($path, $content);
        return $path;
    }
} 