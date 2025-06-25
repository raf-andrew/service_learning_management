<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class GenerateDocsBasicTest extends TestCase
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

    public function test_it_generates_documentation_successfully()
    {
        $this->artisan('docs:generate')
            ->expectsOutput('Generating documentation...')
            ->expectsOutput('Documentation generated successfully!')
            ->assertExitCode(0);
    }

    public function test_it_creates_docs_directory_if_not_exists()
    {
        if (File::exists(base_path('docs'))) {
            File::deleteDirectory(base_path('docs'));
        }
        $this->artisan('docs:generate')
            ->expectsOutput('Generating documentation...')
            ->expectsOutput('Documentation generated successfully!')
            ->assertExitCode(0);
        $this->assertTrue(File::exists(base_path('docs')));
    }
} 