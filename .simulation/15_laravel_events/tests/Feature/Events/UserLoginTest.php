<?php

namespace Tests\Feature\Events;

use App\Events\UserLoggedIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\Channel;
use Carbon\Carbon;
use Tests\TestReporter;

/**
 * @laravel-simulation
 * @component-type Test
 * @test-coverage tests/Feature/Events/UserLoginTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status Complete
 * @job-code EVT-003-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Events
 * @see \App\Events\UserLoggedIn
 * 
 * Test suite for the UserLoggedIn event.
 * Validates event dispatching, broadcasting, and security measures.
 * 
 * @OpenAPI\Tag(name="User Events Tests", description="User login event tests")
 */
class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $event;
    protected $reporter;

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
        
        $this->event = new UserLoggedIn($this->user);
        $this->reporter = new TestReporter('UserLoginTest');
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
            $this->assertInstanceOf(UserLoggedIn::class, $this->event);
            $this->assertEquals($this->user->id, $this->event->user->id);
            $this->assertEquals($this->user->name, $this->event->user->name);
            $this->assertEquals($this->user->email, $this->event->user->email);
            
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
            $this->assertCount(2, $channels);
            $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
            $this->assertInstanceOf(PresenceChannel::class, $channels[1]);
            
            // Test broadcast data
            $data = $this->event->broadcastWith();
            $this->assertArrayHasKey('user', $data);
            $this->assertArrayHasKey('login_time', $data);
            
            $this->assertEquals($this->user->id, $data['user']['id']);
            $this->assertEquals($this->user->name, $data['user']['name']);
            $this->assertEquals($this->user->email, $data['user']['email']);
            
            $this->assertIsString($data['login_time']);
            $this->assertTrue(Carbon::parse($data['login_time'])->isValid());
            
            // Test broadcast name
            $this->assertEquals('user.logged_in', $this->event->broadcastAs());
            
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
            new UserLoggedIn(null);
            
            // Test empty user handling
            $this->expectException(\InvalidArgumentException::class);
            new UserLoggedIn(new User());
            
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
            
            $this->assertInstanceOf(UserLoggedIn::class, $unserialized);
            $this->assertEquals($this->user->id, $unserialized->user->id);
            $this->assertEquals($this->user->name, $unserialized->user->name);
            $this->assertEquals($this->user->email, $unserialized->user->email);
            
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
                ->with('User logged in', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'timestamp' => $this->event->loginTime
                ]);
            
            event($this->event);
            
            $this->reporter->addTestResult('test_event_logging', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_event_logging', false, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clean up after tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->reporter->endTestSuite();
        parent::tearDown();
    }
} 