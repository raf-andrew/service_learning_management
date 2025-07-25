<?php

namespace Tests\Feature\Events;

use App\Events\UserProfileUpdated;
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
 * @test-coverage tests/Feature/Events/UserProfileUpdatedTest.php
 * @api-docs docs/api/events.yaml
 * @security-review docs/security/events.md
 * @qa-status In Progress
 * @job-code EVT-006-TEST
 * @since 1.0.0
 * @author System
 * @package Tests\Feature\Events
 * @see \App\Events\UserProfileUpdated
 * 
 * Test suite for the UserProfileUpdated event.
 * Validates event dispatching, broadcasting, and security measures.
 * 
 * @OpenAPI\Tag(name="User Events Tests", description="User profile update event tests")
 */
class UserProfileUpdatedTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $event;
    protected $reporter;
    protected $changes;

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
        
        $this->changes = [
            'name' => [
                'old' => 'Old Name',
                'new' => 'New Name',
            ],
            'email' => [
                'old' => 'old@example.com',
                'new' => 'new@example.com',
            ],
        ];
        
        $this->event = new UserProfileUpdated($this->user, $this->changes);
        $this->reporter = new TestReporter('UserProfileUpdatedTest');
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
            $this->assertInstanceOf(UserProfileUpdated::class, $this->event);
            $this->assertEquals($this->user->id, $this->event->user->id);
            $this->assertEquals($this->user->name, $this->event->user->name);
            $this->assertEquals($this->user->email, $this->event->user->email);
            $this->assertEquals($this->changes, $this->event->changes);
            
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
            $this->assertArrayHasKey('updated_at', $data);
            $this->assertArrayHasKey('changes', $data);
            
            $this->assertEquals($this->user->id, $data['user']['id']);
            $this->assertEquals($this->user->name, $data['user']['name']);
            $this->assertEquals($this->user->email, $data['user']['email']);
            $this->assertEquals($this->changes, $data['changes']);
            
            $this->assertIsString($data['updated_at']);
            $this->assertTrue(Carbon::parse($data['updated_at'])->isValid());
            
            // Test broadcast name
            $this->assertEquals('user.profile_updated', $this->event->broadcastAs());
            
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
            new UserProfileUpdated(null, $this->changes);
            
            // Test empty user handling
            $this->expectException(\InvalidArgumentException::class);
            new UserProfileUpdated(new User(), $this->changes);
            
            // Test empty changes
            $this->expectException(\InvalidArgumentException::class);
            new UserProfileUpdated($this->user, []);
            
            // Test invalid change format
            $this->expectException(\InvalidArgumentException::class);
            new UserProfileUpdated($this->user, [
                'name' => ['invalid' => 'format'],
            ]);
            
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
            
            $this->assertInstanceOf(UserProfileUpdated::class, $unserialized);
            $this->assertEquals($this->user->id, $unserialized->user->id);
            $this->assertEquals($this->user->name, $unserialized->user->name);
            $this->assertEquals($this->user->email, $unserialized->user->email);
            $this->assertEquals($this->changes, $unserialized->changes);
            
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
                ->with('User profile updated', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'timestamp' => $this->event->updatedAt,
                    'changes' => $this->changes
                ]);
            
            event($this->event);
            
            $this->reporter->addTestResult('test_event_logging', true);
        } catch (\Exception $e) {
            $this->reporter->addTestResult('test_event_logging', false, $e->getMessage());
            throw $e;
        }
    }
} 