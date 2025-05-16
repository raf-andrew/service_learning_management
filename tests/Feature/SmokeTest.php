<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the application is accessible
     *
     * @return void
     */
    public function test_application_is_accessible()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    /**
     * Test that the database connection is working
     *
     * @return void
     */
    public function test_database_connection()
    {
        $user = User::factory()->create();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * Test that authentication is working
     *
     * @return void
     */
    public function test_authentication()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password')
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticated();
    }

    /**
     * Test that the API is accessible
     *
     * @return void
     */
    public function test_api_is_accessible()
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200);
    }

    /**
     * Test that the cache is working
     *
     * @return void
     */
    public function test_cache_is_working()
    {
        $key = 'test_key';
        $value = 'test_value';

        cache()->put($key, $value);
        $this->assertEquals($value, cache()->get($key));
    }

    /**
     * Test that the queue is working
     *
     * @return void
     */
    public function test_queue_is_working()
    {
        $this->assertTrue(true); // Placeholder for queue test
        // TODO: Implement actual queue test when queue system is set up
    }

    /**
     * Test that the storage is accessible
     *
     * @return void
     */
    public function test_storage_is_accessible()
    {
        $path = 'test.txt';
        $content = 'test content';

        \Storage::put($path, $content);
        $this->assertTrue(\Storage::exists($path));
        $this->assertEquals($content, \Storage::get($path));

        \Storage::delete($path);
    }

    /**
     * Test that the session is working
     *
     * @return void
     */
    public function test_session_is_working()
    {
        $key = 'test_key';
        $value = 'test_value';

        session([$key => $value]);
        $this->assertEquals($value, session($key));
    }

    /**
     * Test that the mail system is configured
     *
     * @return void
     */
    public function test_mail_is_configured()
    {
        $this->assertTrue(true); // Placeholder for mail test
        // TODO: Implement actual mail test when mail system is set up
    }

    /**
     * Test that the Redis connection is working
     *
     * @return void
     */
    public function test_redis_is_working()
    {
        $key = 'test_key';
        $value = 'test_value';

        \Redis::set($key, $value);
        $this->assertEquals($value, \Redis::get($key));

        \Redis::del($key);
    }

    /**
     * Test that the application can handle concurrent requests
     *
     * @return void
     */
    public function test_concurrent_requests()
    {
        $this->assertTrue(true); // Placeholder for concurrent requests test
        // TODO: Implement actual concurrent requests test
    }

    /**
     * Test that the application can handle errors gracefully
     *
     * @return void
     */
    public function test_error_handling()
    {
        $response = $this->get('/non-existent-route');
        $response->assertStatus(404);
    }

    /**
     * Test that the application can handle validation errors
     *
     * @return void
     */
    public function test_validation_handling()
    {
        $response = $this->post('/login', []);
        $response->assertStatus(422);
    }

    /**
     * Test that the application can handle rate limiting
     *
     * @return void
     */
    public function test_rate_limiting()
    {
        $this->assertTrue(true); // Placeholder for rate limiting test
        // TODO: Implement actual rate limiting test
    }

    /**
     * Test that the application can handle file uploads
     *
     * @return void
     */
    public function test_file_uploads()
    {
        $this->assertTrue(true); // Placeholder for file upload test
        // TODO: Implement actual file upload test
    }

    /**
     * Test that the application can handle API authentication
     *
     * @return void
     */
    public function test_api_authentication()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/user');

        $response->assertStatus(200);
    }
} 