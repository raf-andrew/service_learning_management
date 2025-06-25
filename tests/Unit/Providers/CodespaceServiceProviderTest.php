<?php

namespace Tests\Unit\Providers;

use Tests\TestCase;
use App\Providers\CodespaceServiceProvider;
use App\Services\CodespaceService;
use App\Services\DeveloperCredentialService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Mockery;

class CodespaceServiceProviderTest extends TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new CodespaceServiceProvider($this->app);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_registers_codespace_service()
    {
        $this->provider->register();

        $this->assertInstanceOf(
            CodespaceService::class,
            $this->app->make(CodespaceService::class)
        );
    }

    public function test_it_merges_config()
    {
        Config::set('codespaces', [
            'enabled' => true,
            'repository' => 'test/repo'
        ]);

        $this->provider->register();

        $this->assertTrue(Config::get('codespaces.enabled'));
        $this->assertEquals('test/repo', Config::get('codespaces.repository'));
    }

    public function test_it_publishes_config()
    {
        $this->provider->boot();

        $this->assertFileExists(config_path('codespaces.php'));
    }

    public function test_it_loads_routes()
    {
        $this->provider->boot();

        $this->assertTrue(Route::has('codespaces.list'));
        $this->assertTrue(Route::has('codespaces.create'));
        $this->assertTrue(Route::has('codespaces.delete'));
        $this->assertTrue(Route::has('codespaces.rebuild'));
    }

    public function test_it_registers_commands()
    {
        $this->provider->boot();

        $this->assertArrayHasKey(
            \App\Console\Commands\CodespaceCommand::class,
            $this->app['Illuminate\Contracts\Console\Kernel']->all()
        );
    }

    public function test_it_handles_missing_config_file()
    {
        if (file_exists(config_path('codespaces.php'))) {
            unlink(config_path('codespaces.php'));
        }

        $this->provider->boot();

        $this->assertFileExists(config_path('codespaces.php'));
    }

    public function test_it_handles_invalid_config()
    {
        Config::set('codespaces', 'invalid-config');

        $this->provider->register();

        $this->assertIsArray(Config::get('codespaces'));
    }

    public function test_it_handles_missing_routes_file()
    {
        if (file_exists(base_path('routes/codespaces.php'))) {
            unlink(base_path('routes/codespaces.php'));
        }

        $this->provider->boot();

        $this->assertFileExists(base_path('routes/codespaces.php'));
    }

    public function test_it_handles_invalid_routes()
    {
        file_put_contents(base_path('routes/codespaces.php'), '<?php invalid-routes;');

        $this->provider->boot();

        $this->assertFileExists(base_path('routes/codespaces.php'));
    }

    public function test_it_handles_missing_commands()
    {
        $this->app['Illuminate\Contracts\Console\Kernel'] = Mockery::mock('Illuminate\Contracts\Console\Kernel');
        $this->app['Illuminate\Contracts\Console\Kernel']->shouldReceive('all')->andReturn([]);

        $this->provider->boot();

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function test_it_handles_invalid_commands()
    {
        $this->app['Illuminate\Contracts\Console\Kernel'] = Mockery::mock('Illuminate\Contracts\Console\Kernel');
        $this->app['Illuminate\Contracts\Console\Kernel']->shouldReceive('all')->andReturn([
            'invalid-command' => 'InvalidCommand'
        ]);

        $this->provider->boot();

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function test_it_handles_permission_issues()
    {
        if (file_exists(config_path('codespaces.php'))) {
            chmod(config_path('codespaces.php'), 0444);
        }

        $this->provider->boot();

        if (file_exists(config_path('codespaces.php'))) {
            chmod(config_path('codespaces.php'), 0644);
        }

        $this->assertTrue(true); // If we get here, no exception was thrown
    }
} 