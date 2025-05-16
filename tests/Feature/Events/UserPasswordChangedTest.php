<?php

namespace Tests\Feature\Events;

use App\Events\UserPasswordChanged;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Broadcasting\PrivateChannel;
use Carbon\Carbon;
use Tests\TestReporter;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/Events/UserPasswordChangedTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status In Progress
 * @job-code EVT-005-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Events
 * @see \App\Events\UserPasswordChanged
 * 
 * Test suite for the UserPasswordChanged event.
 * Validates event dispatching, broadcasting, and security measures.
 * 
 * @OpenAPI\Tag(name="User Events Tests", description="User password change event tests")
 */
class UserPasswordChangedTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $event;
    protected $reporter;
    protected $ipAddress = '192.168.1.1';

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Broadcast::fake();
        
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->event = new UserPasswordChanged($this->user, $this->ipAddress);
        $this->reporter = new TestReporter('UserPasswordChangedTest');
    }

    /**
     * Test event creation and basic functionality.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event creation and basic functionality",
     *     description="Verifies event creation, data validation, and basic functionality"
     * )
     */
    public function test_event_creation_and_basic_functionality()
    {
        $this->reporter->startTest('test_event_creation_and_basic_functionality');
        
        try {
            $this->assertInstanceOf(UserPasswordChanged::class, $this->event);
            $this->assertEquals($this->user->id, $this->event->user->id);
            $this->assertEquals($this->user->name, $this->event->user->name);
            $this->assertEquals($this->user->email, $this->event->user->email);
            $this->assertEquals($this->ipAddress, $this->event->ipAddress);
            
            $this->reporter->addTestResult('test_event_creation_and_basic_functionality', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_event_creation_and_basic_functionality', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test event broadcasting functionality.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event broadcasting",
     *     description="Verifies event broadcasting on correct channels with proper data"
     * )
     */
    public function test_event_broadcasting()
    {
        $this->reporter->startTest('test_event_broadcasting');
        
        try {
            // Test channel configuration
            $channels = $this->event->broadcastOn();
            $this->assertCount(1, $channels);
            $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
            
            // Test broadcast data
            $data = $this->event->broadcastWith();
            $this->assertArrayHasKey('user', $data);
            $this->assertArrayHasKey('changed_at', $data);
            $this->assertArrayHasKey('ip_address', $data);
            
            $this->assertEquals($this->user->id, $data['user']['id']);
            $this->assertEquals($this->user->name, $data['user']['name']);
            $this->assertEquals($this->user->email, $data['user']['email']);
            $this->assertEquals($this->ipAddress, $data['ip_address']);
            
            $this->assertIsString($data['changed_at']);
            $this->assertTrue(Carbon::parse($data['changed_at'])->isValid());
            
            // Test broadcast name
            $this->assertEquals('user.password_changed', $this->event->broadcastAs());
            
            $this->reporter->addTestResult('test_event_broadcasting', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_event_broadcasting', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test event security measures.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event security",
     *     description="Verifies event security measures and error handling"
     * )
     */
    public function test_event_security()
    {
        $this->reporter->startTest('test_event_security');
        
        try {
            // Test invalid user handling
            $this->expectException(\InvalidArgumentException::class);
            new UserPasswordChanged(null, $this->ipAddress);
            
            // Test empty user handling
            $this->expectException(\InvalidArgumentException::class);
            new UserPasswordChanged(new User(), $this->ipAddress);
            
            // Test invalid IP address
            $this->expectException(\InvalidArgumentException::class);
            new UserPasswordChanged($this->user, 'invalid-ip');
            
            // Test broadcast conditions
            $this->user->should_broadcast = false;
            $this->assertFalse($this->event->broadcastWhen());
            
            $this->user->should_broadcast = true;
            $this->assertTrue($this->event->broadcastWhen());
            
            $this->reporter->addTestResult('test_event_security', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_event_security', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test event serialization and data integrity.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event serialization",
     *     description="Verifies event serialization and data integrity"
     * )
     */
    public function test_event_serialization()
    {
        $this->reporter->startTest('test_event_serialization');
        
        try {
            $serialized = serialize($this->event);
            $unserialized = unserialize($serialized);
            
            $this->assertInstanceOf(UserPasswordChanged::class, $unserialized);
            $this->assertEquals($this->user->id, $unserialized->user->id);
            $this->assertEquals($this->user->name, $unserialized->user->name);
            $this->assertEquals($this->user->email, $unserialized->user->email);
            $this->assertEquals($this->ipAddress, $unserialized->ipAddress);
            
            $this->reporter->addTestResult('test_event_serialization', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_event_serialization', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test event logging and monitoring.
     *
     * @test
     * @return void
     * @OpenAPI\Test(
     *     summary="Test event logging",
     *     description="Verifies event logging and monitoring functionality"
     * )
     */
    public function test_event_logging()
    {
        $this->reporter->startTest('test_event_logging');
        
        try {
            Log::shouldReceive('info')
                ->once()
                ->with('User password changed', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'timestamp' => $this->event->changedAt,
                    'ip_address' => $this->ipAddress
                ]);
            
            event($this->event);
            
            $this->reporter->addTestResult('test_event_logging', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_event_logging', false, $e->getMessage());
            throw $e;
        }
    }
} 