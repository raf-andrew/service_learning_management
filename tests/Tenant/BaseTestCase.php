<?php

namespace Tests\Tenant;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class BaseTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupErrorHandling();
    }

    protected function setupErrorHandling(): void
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $error = [
                'type' => $errno,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            file_put_contents(
                base_path('.errors/tenant_' . date('Y-m-d_H-i-s') . '.json'),
                json_encode($error, JSON_PRETTY_PRINT)
            );
            return true;
        });
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_error_handler();
    }

    protected function recordFailure(string $testName, string $message, array $context = []): void
    {
        $failure = [
            'test' => $testName,
            'message' => $message,
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        file_put_contents(
            base_path('.failures/tenant_' . date('Y-m-d_H-i-s') . '.json'),
            json_encode($failure, JSON_PRETTY_PRINT)
        );
    }
} 