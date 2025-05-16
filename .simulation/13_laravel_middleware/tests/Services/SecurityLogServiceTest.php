<?php

namespace Tests\Services;

use App\Services\SecurityLogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\TestCase;

class SecurityLogServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SecurityLogService();
    }

    public function testLogsSecurityEventWithDefaultLevel()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'Test event', \Mockery::on(function ($context) {
                return isset($context['timestamp']) &&
                    isset($context['ip']) &&
                    isset($context['user_agent']) &&
                    isset($context['url']) &&
                    isset($context['method']);
            }));

        $this->service->log('Test event');
    }

    public function testLogsSecurityEventWithCustomLevel()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->once()
            ->with('error', 'Test event', \Mockery::any());

        $this->service->log('Test event', [], 'error');
    }

    public function testLogsSecurityEventWithCustomContext()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'Test event', \Mockery::on(function ($context) {
                return $context['custom_field'] === 'custom_value';
            }));

        $this->service->log('Test event', ['custom_field' => 'custom_value']);
    }

    public function testRespectsMinimumLogLevel()
    {
        Config::set('security.logging.level', 'error');

        Log::shouldReceive('channel')->never();
        Log::shouldReceive('log')->never();

        $this->service->log('Test event', [], 'warning');
    }

    public function testUsesCustomChannel()
    {
        Config::set('security.logging.channel', 'custom');

        Log::shouldReceive('channel')
            ->once()
            ->with('custom')
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'Test event', \Mockery::any());

        $this->service->log('Test event');
    }

    public function testDisablesLoggingWhenConfigured()
    {
        Config::set('security.logging.enabled', false);

        Log::shouldReceive('channel')->never();
        Log::shouldReceive('log')->never();

        $this->service->log('Test event');
    }

    public function testConvenienceMethods()
    {
        Log::shouldReceive('channel')
            ->times(3)
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('log')
            ->once()
            ->with('warning', 'Warning event', \Mockery::any());

        Log::shouldReceive('log')
            ->once()
            ->with('error', 'Error event', \Mockery::any());

        Log::shouldReceive('log')
            ->once()
            ->with('info', 'Info event', \Mockery::any());

        $this->service->warning('Warning event');
        $this->service->error('Error event');
        $this->service->info('Info event');
    }
} 