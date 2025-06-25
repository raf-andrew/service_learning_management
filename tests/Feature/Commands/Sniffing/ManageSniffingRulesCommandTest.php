<?php

namespace Tests\Feature\Commands\Sniffing;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Mockery;

class ManageSniffingRulesCommandTest extends TestCase
{
    private static $fileExists = false;

    public function setUp(): void
    {
        parent::setUp();
        \Mockery::getConfiguration()->allowMockingNonExistentMethods(false);
        File::shouldReceive('exists')->andReturnUsing(function ($path = null) {
            if ($path && strpos($path, 'TestRuleSniff.php') !== false) {
                return self::$fileExists;
            }
            return false;
        });
        File::shouldReceive('put')->andReturn(true);
        File::shouldReceive('delete')->andReturn(true);
        File::shouldReceive('get')->andReturn('/**\n * Old description\n */\nprotected $code = \'OLD\';\nprotected $severity = \'error\';');
        File::shouldReceive('makeDirectory')->andReturn(true);
        File::shouldReceive('glob')->andReturn([]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_adds_a_rule_and_outputs_success_message()
    {
        self::$fileExists = false;
        $this->artisan('sniffing:rules add --type=custom --name=TestRule --description="Test desc" --code=TEST --severity=error')
            ->expectsOutput('Rule TestRule created successfully.')
            ->assertExitCode(0);
    }

    public function test_it_removes_a_rule_and_outputs_success_message()
    {
        self::$fileExists = true;
        $this->artisan('sniffing:rules remove --name=TestRule')
            ->expectsOutput('Rule TestRule removed successfully.')
            ->assertExitCode(0);
    }

    public function test_it_updates_a_rule_and_outputs_success_message()
    {
        self::$fileExists = true;
        $this->artisan('sniffing:rules update --name=TestRule --description="Updated desc" --code=NEW --severity=warning')
            ->expectsOutput('Rule TestRule updated successfully.')
            ->assertExitCode(0);
    }

    public function test_it_handles_missing_required_options_gracefully()
    {
        $this->artisan('sniffing:rules add')
            ->expectsOutput('Missing required options. Please provide --type, --name, --description, and --code.')
            ->assertExitCode(1);
    }

    public function test_it_handles_invalid_severity_gracefully()
    {
        $this->artisan('sniffing:rules add --type=custom --name=TestRule --description="Test desc" --code=TEST --severity=invalid')
            ->expectsOutput('Invalid severity level. Must be one of: error, warning, info')
            ->assertExitCode(1);
    }
} 