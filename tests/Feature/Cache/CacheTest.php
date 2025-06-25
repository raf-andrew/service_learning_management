<?php

namespace Tests\Feature\Cache;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_write_read()
    {
        $key = 'test_key';
        $value = 'test_value';

        // Test write
        $writeResult = Cache::put($key, $value, 60);
        $this->assertTrue($writeResult, 'Cache write operation failed');

        // Test read
        $readValue = Cache::get($key);
        $this->assertEquals($value, $readValue, 'Cache read value does not match written value');
        
        // Log success
        fwrite(STDERR, "Cache write/read test passed successfully\n");
    }

    public function test_cache_delete()
    {
        $key = 'test_delete_key';
        $value = 'test_delete_value';

        // Write value
        Cache::put($key, $value, 60);
        $this->assertTrue(Cache::has($key), 'Cache key not found after write');

        // Delete value
        $deleteResult = Cache::forget($key);
        $this->assertTrue($deleteResult, 'Cache delete operation failed');

        // Verify deletion
        $this->assertNull(Cache::get($key), 'Cache key still exists after deletion');
        
        // Log success
        fwrite(STDERR, "Cache delete test passed successfully\n");
    }

    public function test_cache_tags()
    {
        $key = 'test_tagged_key';
        $value = 'test_tagged_value';
        $tag = 'test_tag';

        // Write tagged value
        Cache::tags($tag)->put($key, $value, 60);
        $this->assertTrue(Cache::tags($tag)->has($key), 'Tagged cache key not found after write');

        // Read tagged value
        $readValue = Cache::tags($tag)->get($key);
        $this->assertEquals($value, $readValue, 'Tagged cache read value does not match written value');

        // Flush tag
        Cache::tags($tag)->flush();
        $this->assertNull(Cache::tags($tag)->get($key), 'Tagged cache key still exists after flush');
        
        // Log success
        fwrite(STDERR, "Cache tags test passed successfully\n");
    }
} 