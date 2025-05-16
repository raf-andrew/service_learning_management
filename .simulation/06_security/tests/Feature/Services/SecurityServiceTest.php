<?php

namespace Tests\Feature\Services;

use App\Models\SecurityLog;
use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityService $securityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SecurityService();
    }

    public function test_can_log_security_event()
    {
        $user = User::factory()->create();
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->headers->set('User-Agent', 'Test Browser');

        $log = $this->securityService->logEvent(
            'login_attempt',
            'medium',
            'User attempted to log in',
            ['attempt_count' => 1],
            $user,
            $request
        );

        $this->assertInstanceOf(SecurityLog::class, $log);
        $this->assertEquals('login_attempt', $log->event_type);
        $this->assertEquals('medium', $log->severity);
        $this->assertEquals('User attempted to log in', $log->description);
        $this->assertEquals('192.168.1.1', $log->ip_address);
        $this->assertEquals('Test Browser', $log->user_agent);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals(['attempt_count' => 1], $log->metadata);
    }

    public function test_can_get_events_with_filters()
    {
        // Create test events
        SecurityLog::factory()->count(3)->create([
            'event_type' => 'login_attempt',
            'severity' => 'medium',
        ]);

        SecurityLog::factory()->create([
            'event_type' => 'api_access',
            'severity' => 'high',
        ]);

        // Test filtering by event type
        $loginEvents = $this->securityService->getEvents(['event_type' => 'login_attempt']);
        $this->assertCount(3, $loginEvents);

        // Test filtering by severity
        $highSeverityEvents = $this->securityService->getEvents(['severity' => 'high']);
        $this->assertCount(1, $highSeverityEvents);
    }

    public function test_can_block_and_unblock_ip()
    {
        $ipAddress = '192.168.1.1';

        // Test blocking
        $this->securityService->blockIp($ipAddress, 60);
        $this->assertTrue($this->securityService->isIpBlocked($ipAddress));

        // Test unblocking
        $this->securityService->unblockIp($ipAddress);
        $this->assertFalse($this->securityService->isIpBlocked($ipAddress));
    }

    public function test_can_detect_suspicious_activity()
    {
        $ipAddress = '192.168.1.1';
        $eventType = 'login_attempt';

        // Test normal activity
        $this->assertFalse($this->securityService->isSuspiciousActivity($ipAddress, $eventType));

        // Simulate multiple attempts
        for ($i = 0; $i < 5; $i++) {
            $this->securityService->isSuspiciousActivity($ipAddress, $eventType);
        }

        // Should now be considered suspicious
        $this->assertTrue($this->securityService->isSuspiciousActivity($ipAddress, $eventType));
    }

    public function test_triggers_alert_for_high_severity_events()
    {
        $log = $this->securityService->logEvent(
            'security_alert',
            'high',
            'Suspicious activity detected'
        );

        $this->assertEquals('alerted', $log->fresh()->status);
    }

    public function test_does_not_trigger_alert_for_low_severity_events()
    {
        $log = $this->securityService->logEvent(
            'api_access',
            'low',
            'API endpoint accessed'
        );

        $this->assertEquals('pending', $log->fresh()->status);
    }

    public function test_can_filter_events_by_time_range()
    {
        // Create events at different times
        SecurityLog::factory()->create([
            'created_at' => now()->subDays(2),
        ]);

        SecurityLog::factory()->create([
            'created_at' => now()->subDay(),
        ]);

        SecurityLog::factory()->create([
            'created_at' => now(),
        ]);

        $events = $this->securityService->getEvents([
            'start_date' => now()->subDays(2)->toDateTimeString(),
            'end_date' => now()->subDay()->toDateTimeString(),
        ]);

        $this->assertCount(2, $events);
    }
} 