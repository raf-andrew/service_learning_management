<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CodespacesTestTest extends TestCase
{
    public function test_codespaces_test_command_exists()
    {
        // Check if the command exists in Artisan::all()
        $this->assertTrue(array_key_exists('codespaces:test', Artisan::all()));
    }

    public function test_codespaces_test_command_has_proper_signature()
    {
        $command = Artisan::all()['codespaces:test'] ?? null;
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