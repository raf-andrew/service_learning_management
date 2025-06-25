<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class GenerateDocsContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!File::exists(base_path('docs'))) {
            File::makeDirectory(base_path('docs'));
        }
    }

    protected function tearDown(): void
    {
        if (File::exists(base_path('docs'))) {
            File::deleteDirectory(base_path('docs'));
        }
        parent::tearDown();
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
} 