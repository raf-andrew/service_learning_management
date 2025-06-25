<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\CodespacesTestTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, CodespacesTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Force codespaces.enabled to false to bypass CodespacesTestTrait logic in tests
        \Illuminate\Support\Facades\Config::set('codespaces.enabled', false);

        // Configure view paths
        View::addLocation(resource_path('views'));
        View::addLocation(base_path('resources/views'));

        if (Config::get('codespaces.enabled', false)) {
            $this->setUpCodespacesTest();
        }
    }

    protected function tearDown(): void
    {
        if (Config::get('codespaces.enabled', false)) {
            $this->tearDownCodespacesTest();
        }

        parent::tearDown();
    }

    protected function configureTestDatabase(): void
    {
        if (Config::get('codespaces.enabled', false)) {
            Config::set('database.connections.mysql.database', 
                Config::get('codespaces.services.mysql.database') . '_test'
            );
        }
    }

    protected function configureTestCache(): void
    {
        if (Config::get('codespaces.enabled', false)) {
            Config::set('cache.default', 'redis');
            Config::set('cache.stores.redis', [
                'driver' => 'redis',
                'connection' => 'default',
            ]);
        }
    }

    protected function cleanupTestData(): void
    {
        if (Config::get('codespaces.enabled', false)) {
            // Clear cache
            $this->app['cache']->flush();

            // Reset database to a clean state
            $this->artisan('migrate:fresh', ['--env' => 'testing']);
        }
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }
} 