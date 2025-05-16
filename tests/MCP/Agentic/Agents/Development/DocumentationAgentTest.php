<?php

namespace Tests\MCP\Agentic\Agents\Development;

use Tests\MCP\Agentic\BaseAgenticTestCase;
use App\MCP\Agentic\Agents\Development\DocumentationAgent;
use App\MCP\Agentic\Core\Services\TaskManager;
use App\MCP\Agentic\Core\Services\AuditLogger;
use App\MCP\Agentic\Core\Services\AccessControl;

class DocumentationAgentTest extends BaseAgenticTestCase
{
    private DocumentationAgent $agent;
    private TaskManager $taskManager;
    private AuditLogger $auditLogger;
    private AccessControl $accessControl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskManager = $this->createMock(TaskManager::class);
        $this->auditLogger = $this->createMock(AuditLogger::class);
        $this->accessControl = $this->createMock(AccessControl::class);

        $this->agent = new DocumentationAgent(
            $this->taskManager,
            $this->auditLogger,
            $this->accessControl
        );
    }

    public function test_agent_initialization(): void
    {
        $this->assertEquals('documentation', $this->agent->getType());
        $this->assertEquals([
            'api_documentation',
            'code_documentation',
            'user_guides',
            'changelog_management',
            'documentation_validation',
        ], $this->agent->getCapabilities());
    }

    public function test_can_generate_api_docs(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['generate_api_docs', ['path' => 'test/path']],
                ['api_docs_complete', $this->anything()]
            );

        $results = $this->agent->generateApiDocs('test/path');

        $this->assertArrayHasKey('endpoints', $results);
        $this->assertArrayHasKey('examples', $results);
        $this->assertArrayHasKey('authentication', $results);
        $this->assertArrayHasKey('rate_limits', $results);
        $this->assertArrayHasKey('error_handling', $results);
    }

    public function test_cannot_generate_api_docs_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API documentation generation not allowed in current environment');

        $this->agent->generateApiDocs('test/path');
    }

    public function test_can_generate_code_docs(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['generate_code_docs', ['path' => 'test/path']],
                ['code_docs_complete', $this->anything()]
            );

        $results = $this->agent->generateCodeDocs('test/path');

        $this->assertArrayHasKey('phpdoc', $results);
        $this->assertArrayHasKey('classes', $results);
        $this->assertArrayHasKey('methods', $results);
        $this->assertArrayHasKey('properties', $results);
        $this->assertArrayHasKey('types', $results);
    }

    public function test_cannot_generate_code_docs_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Code documentation generation not allowed in current environment');

        $this->agent->generateCodeDocs('test/path');
    }

    public function test_can_generate_user_guides(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['generate_user_guides', ['path' => 'test/path']],
                ['user_guides_complete', $this->anything()]
            );

        $results = $this->agent->generateUserGuides('test/path');

        $this->assertArrayHasKey('installation', $results);
        $this->assertArrayHasKey('configuration', $results);
        $this->assertArrayHasKey('usage', $results);
        $this->assertArrayHasKey('troubleshooting', $results);
        $this->assertArrayHasKey('best_practices', $results);
    }

    public function test_cannot_generate_user_guides_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User guide generation not allowed in current environment');

        $this->agent->generateUserGuides('test/path');
    }

    public function test_can_manage_changelog(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['manage_changelog', ['path' => 'test/path']],
                ['changelog_complete', $this->anything()]
            );

        $results = $this->agent->manageChangelog('test/path');

        $this->assertArrayHasKey('versions', $results);
        $this->assertArrayHasKey('changes', $results);
        $this->assertArrayHasKey('breaking', $results);
        $this->assertArrayHasKey('features', $results);
        $this->assertArrayHasKey('bugs', $results);
    }

    public function test_cannot_manage_changelog_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Changelog management not allowed in current environment');

        $this->agent->manageChangelog('test/path');
    }

    public function test_can_validate_documentation(): void
    {
        $this->setupEnvironment('testing');
        $this->auditLogger->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                ['validate_documentation', ['path' => 'test/path']],
                ['validation_complete', $this->anything()]
            );

        $results = $this->agent->validateDocumentation('test/path');

        $this->assertArrayHasKey('completeness', $results);
        $this->assertArrayHasKey('accuracy', $results);
        $this->assertArrayHasKey('links', $results);
        $this->assertArrayHasKey('examples', $results);
        $this->assertArrayHasKey('format', $results);
    }

    public function test_cannot_validate_documentation_in_production(): void
    {
        $this->setupEnvironment('production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Documentation validation not allowed in current environment');

        $this->agent->validateDocumentation('test/path');
    }

    private function setupEnvironment(string $environment): void
    {
        $this->accessControl->method('getEnvironment')
            ->willReturn($environment);
    }
} 