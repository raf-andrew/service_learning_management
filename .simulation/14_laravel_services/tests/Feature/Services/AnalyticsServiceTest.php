<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsReport;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $analyticsService;
    protected $user;
    protected $course;
    protected $payment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test course
        $this->course = Course::factory()->create();

        // Create test payment
        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'status' => 'completed'
        ]);

        // Create AnalyticsService instance
        $this->analyticsService = new AnalyticsService(
            new AnalyticsEvent(),
            new AnalyticsReport()
        );
    }

    public function test_tracks_event_successfully()
    {
        $eventType = 'test_event';
        $data = ['test_key' => 'test_value'];

        $event = $this->analyticsService->trackEvent($eventType, $data, $this->user);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals($eventType, $event->event_type);
        $this->assertEquals($this->user->id, $event->user_id);
        $this->assertEquals($data, $event->data);
        $this->assertNotNull($event->ip_address);
        $this->assertNotNull($event->user_agent);

        // Check if event is cached
        $this->assertTrue(Cache::has("analytics_event_{$event->id}"));
    }

    public function test_generates_report_successfully()
    {
        $reportType = 'user_activity';
        $parameters = [
            'user_id' => $this->user->id,
            'start_date' => now()->subDays(7),
            'end_date' => now()
        ];

        $report = $this->analyticsService->generateReport($reportType, $parameters);

        $this->assertInstanceOf(AnalyticsReport::class, $report);
        $this->assertEquals($reportType, $report->report_type);
        $this->assertEquals($parameters, $report->parameters);
        $this->assertEquals('completed', $report->status);
        $this->assertNotNull($report->data);
    }

    public function test_gets_user_analytics_successfully()
    {
        // Create some test events
        AnalyticsEvent::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'event_type' => 'test_event'
        ]);

        $filters = [
            'start_date' => now()->subDays(7),
            'end_date' => now()
        ];

        $analytics = $this->analyticsService->getUserAnalytics($this->user, $filters);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('events', $analytics);
        $this->assertArrayHasKey('summary', $analytics);
        $this->assertCount(3, $analytics['events']);
        $this->assertEquals(3, $analytics['summary']['total_events']);
    }

    public function test_gets_course_analytics_successfully()
    {
        // Create some test events
        AnalyticsEvent::factory()->count(3)->create([
            'data' => ['course_id' => $this->course->id],
            'event_type' => 'course_interaction'
        ]);

        $filters = [
            'start_date' => now()->subDays(7),
            'end_date' => now()
        ];

        $analytics = $this->analyticsService->getCourseAnalytics($this->course, $filters);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('events', $analytics);
        $this->assertArrayHasKey('summary', $analytics);
        $this->assertCount(3, $analytics['events']);
        $this->assertEquals(3, $analytics['summary']['total_interactions']);
    }

    public function test_gets_payment_analytics_successfully()
    {
        // Create some test payments
        Payment::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'status' => 'completed'
        ]);

        $filters = [
            'start_date' => now()->subDays(7),
            'end_date' => now(),
            'status' => 'completed'
        ];

        $analytics = $this->analyticsService->getPaymentAnalytics($filters);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('payments', $analytics);
        $this->assertArrayHasKey('summary', $analytics);
        $this->assertCount(4, $analytics['payments']); // Including the one from setUp
        $this->assertEquals(400.00, $analytics['summary']['total_revenue']);
    }

    public function test_throws_exception_for_invalid_report_type()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown report type: invalid_type');

        $this->analyticsService->generateReport('invalid_type', []);
    }

    public function test_generates_user_activity_report()
    {
        // Create test events
        AnalyticsEvent::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'event_type' => 'test_event'
        ]);

        $parameters = [
            'user_id' => $this->user->id,
            'start_date' => now()->subDays(7),
            'end_date' => now()
        ];

        $report = $this->analyticsService->generateReport('user_activity', $parameters);

        $this->assertEquals('user_activity', $report->report_type);
        $this->assertEquals('completed', $report->status);
        $this->assertEquals(3, $report->data['total_events']);
    }

    public function test_generates_course_performance_report()
    {
        // Create test events
        AnalyticsEvent::factory()->count(3)->create([
            'data' => ['course_id' => $this->course->id],
            'event_type' => 'course_interaction'
        ]);

        $parameters = [
            'course_id' => $this->course->id,
            'start_date' => now()->subDays(7),
            'end_date' => now()
        ];

        $report = $this->analyticsService->generateReport('course_performance', $parameters);

        $this->assertEquals('course_performance', $report->report_type);
        $this->assertEquals('completed', $report->status);
        $this->assertEquals(3, $report->data['total_interactions']);
    }

    public function test_generates_payment_summary_report()
    {
        // Create test payments
        Payment::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'amount' => 100.00,
            'status' => 'completed'
        ]);

        $parameters = [
            'start_date' => now()->subDays(7),
            'end_date' => now()
        ];

        $report = $this->analyticsService->generateReport('payment_summary', $parameters);

        $this->assertEquals('payment_summary', $report->report_type);
        $this->assertEquals('completed', $report->status);
        $this->assertEquals(400.00, $report->data['total_revenue']); // Including the one from setUp
    }
} 