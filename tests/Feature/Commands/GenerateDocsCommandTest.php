<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mockery;

class GenerateDocsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a temporary docs directory for testing
        if (!File::exists(base_path('docs'))) {
            File::makeDirectory(base_path('docs'));
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (File::exists(base_path('docs'))) {
            File::deleteDirectory(base_path('docs'));
        }
        
        parent::tearDown();
    }

    public function test_it_generates_documentation_successfully()
    {
        $this->artisan('docs:generate')
            ->expectsOutput('Generating documentation...')
            ->expectsOutput('Documentation generated successfully!')
            ->assertExitCode(0);
    }

    public function test_it_creates_docs_directory_if_not_exists()
    {
        // Remove docs directory if it exists
        if (File::exists(base_path('docs'))) {
            File::deleteDirectory(base_path('docs'));
        }

        $this->artisan('docs:generate')
            ->expectsOutput('Generating documentation...')
            ->expectsOutput('Documentation generated successfully!')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(base_path('docs')));
    }

    public function test_it_generates_main_readme_file()
    {
        $this->artisan('docs:generate');

        $readmePath = base_path('docs/README.md');
        $this->assertTrue(File::exists($readmePath));
        
        $content = File::get($readmePath);
        $this->assertStringContainsString('# Service Learning Management System', $content);
        $this->assertStringContainsString('GitHub Codespaces', $content);
        $this->assertStringContainsString('Docker-based development environment', $content);
    }

    public function test_it_generates_codespaces_documentation()
    {
        $this->artisan('docs:generate');

        $codespacesPath = base_path('docs/codespaces.md');
        $this->assertTrue(File::exists($codespacesPath));
        
        $content = File::get($codespacesPath);
        $this->assertStringContainsString('# GitHub Codespaces Guide', $content);
        $this->assertStringContainsString('php artisan codespace:create', $content);
        $this->assertStringContainsString('Environment Management', $content);
    }

    public function test_it_generates_docker_documentation()
    {
        $this->artisan('docs:generate');

        $dockerPath = base_path('docs/docker.md');
        $this->assertTrue(File::exists($dockerPath));
        
        $content = File::get($dockerPath);
        $this->assertStringContainsString('# Docker Setup Guide', $content);
        $this->assertStringContainsString('docker-compose.yml', $content);
        $this->assertStringContainsString('MySQL', $content);
        $this->assertStringContainsString('Redis', $content);
    }

    public function test_it_generates_api_documentation()
    {
        $this->artisan('docs:generate');

        $apiPath = base_path('docs/api.md');
        $this->assertTrue(File::exists($apiPath));
        
        $content = File::get($apiPath);
        $this->assertStringContainsString('# API Documentation', $content);
        $this->assertStringContainsString('Authentication', $content);
        $this->assertStringContainsString('Error Handling', $content);
    }

    public function test_it_generates_feature_documentation()
    {
        $this->artisan('docs:generate');

        $featurePath = base_path('docs/features.md');
        $this->assertTrue(File::exists($featurePath));
        
        $content = File::get($featurePath);
        $this->assertStringContainsString('# Features', $content);
        $this->assertStringContainsString('Project features and functionality', $content);
    }

    public function test_it_generates_setup_guide()
    {
        $this->artisan('docs:generate');

        $setupPath = base_path('docs/setup.md');
        $this->assertTrue(File::exists($setupPath));
        
        $content = File::get($setupPath);
        $this->assertStringContainsString('# Setup Guide', $content);
        $this->assertStringContainsString('Requirements', $content);
        $this->assertStringContainsString('Installation', $content);
    }

    public function test_it_generates_contribution_guide()
    {
        $this->artisan('docs:generate');

        $contributionPath = base_path('docs/contributing.md');
        $this->assertTrue(File::exists($contributionPath));
        
        $content = File::get($contributionPath);
        $this->assertStringContainsString('# Contribution Guide', $content);
        $this->assertStringContainsString('Guide for contributing to the project', $content);
    }

    public function test_it_handles_file_permission_errors_gracefully()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('File permission tests are not supported on Windows.');
        }
        // Mock File facade to simulate permission error
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('makeDirectory')->andThrow(new \Exception('Permission denied'));

        $this->artisan('docs:generate')
            ->expectsOutput('Generating documentation...')
            ->assertExitCode(1);
    }

    public function test_it_generates_all_required_documentation_files()
    {
        $this->artisan('docs:generate');

        $expectedFiles = [
            'docs/README.md',
            'docs/codespaces.md',
            'docs/docker.md',
            'docs/api.md',
            'docs/features.md',
            'docs/setup.md',
            'docs/contributing.md'
        ];

        foreach ($expectedFiles as $file) {
            $this->assertTrue(File::exists(base_path($file)), "File {$file} was not generated");
        }
    }

    public function test_it_includes_correct_links_in_main_readme()
    {
        $this->artisan('docs:generate');

        $readmePath = base_path('docs/README.md');
        $content = File::get($readmePath);
        
        $this->assertStringContainsString('[Codespaces Guide](docs/codespaces.md)', $content);
        $this->assertStringContainsString('[Docker Setup](docs/docker.md)', $content);
        $this->assertStringContainsString('[API Documentation](docs/api.md)', $content);
    }

    public function test_it_includes_license_information()
    {
        $this->artisan('docs:generate');

        $readmePath = base_path('docs/README.md');
        $content = File::get($readmePath);
        
        $this->assertStringContainsString('MIT License', $content);
        $this->assertStringContainsString('[LICENSE](LICENSE)', $content);
    }

    public function test_it_includes_quick_start_instructions()
    {
        $this->artisan('docs:generate');

        $readmePath = base_path('docs/README.md');
        $content = File::get($readmePath);
        
        $this->assertStringContainsString('## Quick Start', $content);
        $this->assertStringContainsString('php artisan codespace:create', $content);
        $this->assertStringContainsString('GitHub web interface', $content);
    }

    public function test_it_includes_feature_list()
    {
        $this->artisan('docs:generate');

        $readmePath = base_path('docs/README.md');
        $content = File::get($readmePath);
        
        $this->assertStringContainsString('## Features', $content);
        $this->assertStringContainsString('Automated Codespace setup', $content);
        $this->assertStringContainsString('Docker-based development environment', $content);
        $this->assertStringContainsString('GitHub Pages documentation', $content);
    }
} 