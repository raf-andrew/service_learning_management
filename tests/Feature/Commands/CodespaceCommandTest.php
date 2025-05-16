<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CodespaceCommandTest extends TestCase
{
    use RefreshDatabase;

    protected $configFile;
    protected $stateFile;
    protected $scriptFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configFile = base_path('.codespaces/config/codespaces.json');
        $this->stateFile = base_path('.codespaces/state/codespaces.json');
        $this->scriptFile = base_path('.codespaces/scripts/codespace.sh');

        // Create test configuration
        File::makeDirectory(base_path('.codespaces/config'), 0755, true, true);
        File::makeDirectory(base_path('.codespaces/state'), 0755, true, true);
        File::makeDirectory(base_path('.codespaces/scripts'), 0755, true, true);

        // Create test files
        File::put($this->configFile, json_encode([
            'name' => 'test-codespace',
            'version' => '1.0.0',
            'defaults' => [
                'region' => 'us-east-1',
                'machine' => 'basic'
            ]
        ]));

        File::put($this->stateFile, json_encode([
            'version' => '1.0.0',
            'environments' => [
                'development' => [
                    'status' => 'not_created',
                    'name' => null
                ]
            ]
        ]));

        File::put($this->scriptFile, '#!/bin/bash
echo "Executing $1 for $2"');
    }

    protected function tearDown(): void
    {
        // Clean up test files
        File::deleteDirectory(base_path('.codespaces'));

        parent::tearDown();
    }

    /** @test */
    public function it_can_list_codespaces()
    {
        Process::fake([
            'bash .codespaces/scripts/codespace.sh list' => Process::result(
                'Listing Codespaces...',
                '',
                0
            ),
        ]);

        $this->artisan('codespace list')
            ->expectsOutput('Executing Codespace action: list')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_create_codespace()
    {
        Process::fake([
            'bash .codespaces/scripts/codespace.sh create development' => Process::result(
                'Creating Codespace for environment: development',
                '',
                0
            ),
        ]);

        $this->artisan('codespace create development')
            ->expectsOutput('Executing Codespace action: create for environment: development')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_delete_codespace()
    {
        Process::fake([
            'bash .codespaces/scripts/codespace.sh delete development' => Process::result(
                'Deleting Codespace for environment: development',
                '',
                0
            ),
        ]);

        $this->artisan('codespace delete development')
            ->expectsOutput('Executing Codespace action: delete for environment: development')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_rebuild_codespace()
    {
        Process::fake([
            'bash .codespaces/scripts/codespace.sh rebuild development' => Process::result(
                'Rebuilding Codespace for environment: development',
                '',
                0
            ),
        ]);

        $this->artisan('codespace rebuild development')
            ->expectsOutput('Executing Codespace action: rebuild for environment: development')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_can_connect_to_codespace()
    {
        Process::fake([
            'bash .codespaces/scripts/codespace.sh connect development' => Process::result(
                'Connecting to Codespace for environment: development',
                '',
                0
            ),
        ]);

        $this->artisan('codespace connect development')
            ->expectsOutput('Executing Codespace action: connect for environment: development')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_missing_configuration_file()
    {
        File::delete($this->configFile);

        $this->artisan('codespace list')
            ->expectsOutput('Codespaces configuration file not found.')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_handles_missing_state_file()
    {
        File::delete($this->stateFile);

        $this->artisan('codespace list')
            ->expectsOutput('Codespaces state file not found.')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_handles_missing_script_file()
    {
        File::delete($this->scriptFile);

        $this->artisan('codespace list')
            ->expectsOutput('Codespaces script file not found.')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_handles_script_execution_error()
    {
        Process::fake([
            'bash .codespaces/scripts/codespace.sh list' => Process::result(
                '',
                'Script execution failed',
                1
            ),
        ]);

        $this->artisan('codespace list')
            ->expectsOutput('Failed to execute Codespace action: Script execution failed')
            ->assertExitCode(1);
    }
} 