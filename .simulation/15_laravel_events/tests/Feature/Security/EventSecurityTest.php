<?php

namespace Tests\Feature\Security;

use App\Events\BaseEvent;
use App\Listeners\BaseListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Exception;

class TestEvent extends BaseEvent
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('test-channel')
        ];
    }
}

class TestListener extends BaseListener
{
    public function handle($event)
    {
        return true;
    }
}

class EventSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        
        // Define test gate
        Gate::define('access-test-channel', function ($user) {
            return $user->id === 1;
        });
    }

    /** @test */
    public function it_validates_event_data()
    {
        $this->expectException(Exception::class);
        
        $event = new TestEvent(null);
        $listener = new TestListener();
        $listener->handle($event);
    }

    /** @test */
    public function it_enforces_channel_authorization()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $event = new TestEvent(['test' => 'data']);
        
        $this->assertFalse(Gate::allows('access-test-channel', $user));
        $this->assertFalse($event->broadcastWhen());
    }

    /** @test */
    public function it_prevents_unauthorized_event_dispatch()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Event::listen(TestEvent::class, function ($event) {
            return false;
        });

        $event = new TestEvent(['test' => 'data']);
        $this->assertFalse(Event::dispatch($event));
    }

    /** @test */
    public function it_implements_rate_limiting()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // Simulate multiple event dispatches
        for ($i = 0; $i < 10; $i++) {
            Event::dispatch(new TestEvent(['test' => 'data']));
        }

        // Should be rate limited after 5 attempts
        $this->assertTrue(Event::dispatched(TestEvent::class)->count() <= 5);
    }

    /** @test */
    public function it_sanitizes_sensitive_data()
    {
        $event = new TestEvent([
            'password' => 'secret123',
            'token' => 'sensitive-token',
            'email' => 'test@example.com'
        ]);

        $broadcastData = $event->broadcastWith();
        
        $this->assertArrayNotHasKey('password', $broadcastData);
        $this->assertArrayNotHasKey('token', $broadcastData);
        $this->assertEquals('test@example.com', $broadcastData['email']);
    }

    /** @test */
    public function it_validates_listener_input()
    {
        $listener = new TestListener();
        
        $this->expectException(Exception::class);
        $listener->handle('invalid-event');
    }

    /** @test */
    public function it_implements_proper_error_handling()
    {
        $listener = new TestListener();
        $event = new TestEvent(['test' => 'data']);
        
        try {
            $listener->handle($event);
        } catch (Exception $e) {
            $this->assertStringContainsString('Event listener failed', $e->getMessage());
        }
    }

    protected function createUser()
    {
        return (object)['id' => 2]; // Non-authorized user
    }
} 