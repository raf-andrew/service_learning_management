<?php

namespace Tests\Traits;

use App\Services\CodespacesHealthService;
use Illuminate\Support\Facades\Config;

trait CodespacesServiceHealth
{
    protected function ensureServicesHealthy()
    {
        if (!Config::get('codespaces.enabled')) {
            return;
        }

        $healthService = app(CodespacesHealthService::class);
        $health = $healthService->checkHealth();

        if (!$health['healthy']) {
            $this->markTestSkipped('Required services are not healthy: ' . json_encode($health['services']));
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureServicesHealthy();
    }
} 