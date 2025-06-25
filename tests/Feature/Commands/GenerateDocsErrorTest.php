<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class GenerateDocsErrorTest extends TestCase
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

    public function test_it_handles_file_permission_errors_gracefully()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('File permission tests are not supported on Windows.');
        }
        File::shouldReceive('exists')->andReturn(false);
        File::shouldReceive('makeDirectory')->andThrow(new \Exception('Permission denied'));
        $this->artisan('docs:generate')
            ->expectsOutput('Generating documentation...')
            ->assertExitCode(1);
    }
} 