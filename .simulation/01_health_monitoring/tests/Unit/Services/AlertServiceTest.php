<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\ServiceHealth;
use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private $alertService;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alertService = new AlertService();
        
        $this->service = ServiceHealth::create([
            'service_name' => 'test_service',
            'status' => 'healthy',
            'last_check' => now(),
            'response_time' => 0.1
        ]);
    }

    public function test_set_thresholds()
    {
        $newThresholds = [
            'cpu' => 90,
            'memory' => 95,
            'disk' => 98
        ];

        $this->alertService->setThresholds($newThresholds);

        $metrics = [
            [
                'name' => 'cpu',
                'value' => 85,
                'unit' => 'percent'
            ],
            [
                'name' => 'memory',
                'value' => 90,
                'unit' => 'percent'
            ]
        ];

        $alerts = $this->alertService->checkThresholds($metrics);

        $this->assertIsArray($alerts);
        $this->assertEmpty($alerts); // No alerts should be generated with new thresholds
    }

    public function test_check_thresholds()
    {
        $metrics = [
            [
                'name' => 'cpu',
                'value' => 85,
                'unit' => 'percent'
            ],
            [
                'name' => 'memory',
                'value' => 70,
                'unit' => 'percent'
            ],
            [
                'name' => 'disk',
                'value' => 95,
                'unit' => 'percent'
            ]
        ];

        $alerts = $this->alertService->checkThresholds($metrics);

        $this->assertIsArray($alerts);
        $this->assertCount(2, $alerts);
        
        $this->assertEquals('critical', $alerts[0]['level']);
        $this->assertEquals('critical', $alerts[1]['level']);
    }

    public function test_create_alert()
    {
        $alertData = [
            'type' => 'cpu_usage',
            'level' => 'critical',
            'message' => 'CPU usage is above threshold'
        ];

        $alert = $this->alertService->createAlert($this->service, $alertData);

        $this->assertInstanceOf(Alert::class, $alert);
        $this->assertEquals($this->service->id, $alert->service_health_id);
        $this->assertEquals('cpu_usage', $alert->type);
        $this->assertEquals('critical', $alert->level);
        $this->assertEquals('CPU usage is above threshold', $alert->message);
        $this->assertEquals(1, $this->service->error_count);
    }

    public function test_create_warning_alert()
    {
        $alertData = [
            'type' => 'memory_usage',
            'level' => 'warning',
            'message' => 'Memory usage is approaching threshold'
        ];

        $alert = $this->alertService->createAlert($this->service, $alertData);

        $this->assertInstanceOf(Alert::class, $alert);
        $this->assertEquals($this->service->id, $alert->service_health_id);
        $this->assertEquals('memory_usage', $alert->type);
        $this->assertEquals('warning', $alert->level);
        $this->assertEquals('Memory usage is approaching threshold', $alert->message);
        $this->assertEquals(1, $this->service->warning_count);
    }

    public function test_process_alert()
    {
        $alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'cpu_usage',
            'level' => 'critical',
            'message' => 'CPU usage is above threshold'
        ]);

        $this->alertService->processAlert($alert);

        $this->assertTrue($alert->acknowledged);
        $this->assertNotNull($alert->acknowledged_at);
    }

    public function test_send_alert_notification()
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'ALERT: CPU usage is above threshold' &&
                    $context['service'] === 'test_service' &&
                    $context['level'] === 'critical' &&
                    $context['type'] === 'cpu_usage';
            });

        $alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'cpu_usage',
            'level' => 'critical',
            'message' => 'CPU usage is above threshold'
        ]);

        $this->alertService->createAlert($this->service, [
            'type' => 'cpu_usage',
            'level' => 'critical',
            'message' => 'CPU usage is above threshold'
        ]);
    }

    public function test_send_acknowledgment_notification()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Alert acknowledged: CPU usage is above threshold' &&
                    $context['service'] === 'test_service' &&
                    $context['level'] === 'critical' &&
                    $context['type'] === 'cpu_usage';
            });

        $alert = Alert::create([
            'service_health_id' => $this->service->id,
            'type' => 'cpu_usage',
            'level' => 'critical',
            'message' => 'CPU usage is above threshold'
        ]);

        $this->alertService->processAlert($alert);
    }
} 