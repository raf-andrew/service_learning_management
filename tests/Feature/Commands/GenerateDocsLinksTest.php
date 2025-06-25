<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class GenerateDocsLinksTest extends TestCase
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