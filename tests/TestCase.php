<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\CodespacesTestTrait;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, CodespacesTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

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
} 