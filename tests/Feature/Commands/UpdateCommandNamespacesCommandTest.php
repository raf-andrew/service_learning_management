<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class UpdateCommandNamespacesCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('get')->andReturn('<?php\nnamespace App\\Console\\Commands;\nclass DummyCommand {}');
        File::shouldReceive('put')->andReturn(true);
        File::shouldReceive('glob')->andReturn([]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_updates_namespaces_and_outputs_success_message()
    {
        $this->artisan('commands:update-namespaces')
            ->expectsOutput('Namespace updates completed!')
            ->assertExitCode(0);
    }

    public function test_it_handles_missing_files_gracefully()
    {
        File::shouldReceive('exists')->andReturn(false);
        $this->artisan('commands:update-namespaces')
            ->expectsOutput('Namespace updates completed!')
            ->assertExitCode(0);
    }
} 