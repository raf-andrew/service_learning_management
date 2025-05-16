<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CacheManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class CacheManagerTest extends TestCase
{
    protected $cacheManager;
    protected $request;
    protected $response;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheManager = new CacheManager();
        $this->request = new Request();
        $this->response = new Response('Test content', 200);
    }

    public function test_should_cache_get_request()
    {
        $this->request->setMethod('GET');
        $this->assertTrue($this->cacheManager->shouldCache($this->request, $this->response));
    }

    public function test_should_not_cache_post_request()
    {
        $this->request->setMethod('POST');
        $this->assertFalse($this->cacheManager->shouldCache($this->request, $this->response));
    }

    public function test_should_not_cache_error_response()
    {
        $this->request->setMethod('GET');
        $this->response->setStatusCode(500);
        $this->assertFalse($this->cacheManager->shouldCache($this->request, $this->response));
    }

    public function test_should_not_cache_private_response()
    {
        $this->request->setMethod('GET');
        $this->response->headers->set('Cache-Control', 'private');
        $this->assertFalse($this->cacheManager->shouldCache($this->request, $this->response));
    }

    public function test_generates_unique_cache_key()
    {
        $this->request->setMethod('GET');
        $this->request->server->set('REQUEST_URI', '/api/test');
        
        $key1 = $this->cacheManager->getCacheKey($this->request);
        
        $this->request->query->set('param', 'value');
        $key2 = $this->cacheManager->getCacheKey($this->request);
        
        $this->assertNotEquals($key1, $key2);
    }

    public function test_caches_response()
    {
        $this->request->setMethod('GET');
        $this->request->server->set('REQUEST_URI', '/api/test');
        
        $this->cacheManager->cacheResponse($this->request, $this->response);
        
        $key = $this->cacheManager->getCacheKey($this->request);
        $this->assertTrue(Cache::has($key));
    }

    public function test_retrieves_cached_response()
    {
        $this->request->setMethod('GET');
        $this->request->server->set('REQUEST_URI', '/api/test');
        
        $this->cacheManager->cacheResponse($this->request, $this->response);
        
        $cachedResponse = $this->cacheManager->getCachedResponse($this->request);
        
        $this->assertNotNull($cachedResponse);
        $this->assertEquals('Test content', $cachedResponse->getContent());
        $this->assertEquals(200, $cachedResponse->status());
        $this->assertEquals('HIT', $cachedResponse->headers->get('X-Cache'));
    }

    public function test_invalidates_cache()
    {
        $this->request->setMethod('GET');
        $this->request->server->set('REQUEST_URI', '/api/test');
        
        $this->cacheManager->cacheResponse($this->request, $this->response);
        
        $key = $this->cacheManager->getCacheKey($this->request);
        $this->assertTrue(Cache::has($key));
        
        $this->cacheManager->invalidateCache('response:GET:/api/test');
        $this->assertFalse(Cache::has($key));
    }

    public function test_adds_cache_headers()
    {
        $ttl = 3600;
        $this->cacheManager->addCacheHeaders($this->response, $ttl);
        
        $this->assertEquals('public, max-age=' . $ttl, $this->response->headers->get('Cache-Control'));
        $this->assertNotNull($this->response->headers->get('Expires'));
        $this->assertEquals('Accept-Encoding', $this->response->headers->get('Vary'));
    }

    public function test_uses_custom_ttl_from_cache_control()
    {
        $ttl = 1800;
        $this->response->headers->set('Cache-Control', 'public, max-age=' . $ttl);
        
        $this->assertEquals($ttl, $this->cacheManager->getCacheTtl($this->response));
    }

    public function test_uses_default_ttl_when_no_cache_control()
    {
        Config::set('cache.ttl', 7200);
        $this->assertEquals(7200, $this->cacheManager->getCacheTtl($this->response));
    }

    public function test_includes_vary_headers_in_cache_key()
    {
        $this->request->setMethod('GET');
        $this->request->server->set('REQUEST_URI', '/api/test');
        
        $key1 = $this->cacheManager->getCacheKey($this->request);
        
        $this->request->headers->set('Accept-Language', 'en-US');
        $key2 = $this->cacheManager->getCacheKey($this->request);
        
        $this->assertNotEquals($key1, $key2);
    }
} 