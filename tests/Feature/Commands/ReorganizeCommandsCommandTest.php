<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class ReorganizeCommandsCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('makeDirectory')->andReturn(true);
        File::shouldReceive('move')->andReturn(true);
        File::shouldReceive('glob')->andReturn([]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_reorganizes_commands_and_outputs_success_message()
    {
        $this->artisan('commands:reorganize')
            ->expectsOutput('Command reorganization completed!')
            ->assertExitCode(0);
    }

    public function test_it_handles_missing_files_gracefully()
    {
        File::shouldReceive('exists')->andReturn(false);
        $this->artisan('commands:reorganize')
            ->expectsOutput('Command reorganization completed!')
            ->assertExitCode(0);
    }
} 