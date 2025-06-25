<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class ConvertShellScriptsCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('files')->andReturn([]);
        File::shouldReceive('put')->andReturn(true);
        File::shouldReceive('glob')->andReturn([]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_converts_scripts_and_outputs_success_message()
    {
        $this->artisan('commands:convert-scripts')
            ->expectsOutput('Script conversion completed!')
            ->assertExitCode(0);
    }

    public function test_it_handles_missing_script_directories_gracefully()
    {
        File::shouldReceive('exists')->andReturn(false);
        $this->artisan('commands:convert-scripts')
            ->expectsOutput('Script conversion completed!')
            ->assertExitCode(0);
    }
} 