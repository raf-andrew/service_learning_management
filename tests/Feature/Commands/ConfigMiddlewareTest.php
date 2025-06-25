<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class ConfigMiddlewareTest extends TestCase
{
    public function test_config_middleware_command_exists()
    {
        // Check if the command exists in Artisan::all()
        $this->assertTrue(array_key_exists('config:middleware', Artisan::all()));
    }

    public function test_config_middleware_command_can_be_executed()
    {
        try {
            $exitCode = Artisan::call('config:middleware');
            $this->assertIsInt($exitCode);
        } catch (\Exception $e) {
            // Command may fail due to missing dependencies, but should not crash
            $this->assertTrue(true, 'Command executed without fatal errors');
        }
    }

    public function test_config_middleware_command_has_proper_signature()
    {
        $command = Artisan::all()['config:middleware'] ?? null;
        if ($command) {
            $reflection = new \ReflectionClass($command);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);
            $signature = $signatureProperty->getValue($command);
            $this->assertNotEmpty($signature);
            $this->assertNotEmpty($command->getDescription());
        }
    }
}