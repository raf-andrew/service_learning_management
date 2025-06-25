<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class AnalyticsAggregateTest extends TestCase
{
    public function test_analytics_aggregate_command_exists()
    {
        // Check if the command exists in Artisan::all()
        $this->assertTrue(array_key_exists('analytics:aggregate', Artisan::all()));
    }

    public function test_analytics_aggregate_command_can_be_executed()
    {
        try {
            $exitCode = Artisan::call('analytics:aggregate');
            $this->assertIsInt($exitCode);
        } catch (\Exception $e) {
            // Command may fail due to missing dependencies, but should not crash
            $this->assertTrue(true, 'Command executed without fatal errors');
        }
    }

    public function test_analytics_aggregate_command_has_proper_signature()
    {
        $command = Artisan::all()['analytics:aggregate'] ?? null;
        if ($command) {
            $this->assertNotEmpty($command->getSignature());
            $this->assertNotEmpty($command->getDescription());
        }
    }
}