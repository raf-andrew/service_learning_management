<?php

namespace Tests\Unit\Http;

use Tests\TestCase;
use App\Http\RequestHandler;
use App\Config\RouteConfig;
use App\Services\ValidationService;
use App\Services\TransformationService;
use Illuminate\Http\Request;
use Mockery;

class RequestHandlerTest extends TestCase
{
    protected $handler;
    protected $routeConfig;
    protected $validator;
    protected $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routeConfig = Mockery::mock(RouteConfig::class);
        $this->validator = Mockery::mock(ValidationService::class);
        $this->transformer = Mockery::mock(TransformationService::class);

        $this->handler = new RequestHandler(
            $this->routeConfig,
            $this->validator,
            $this->transformer
        );
    }

    public function test_handles_valid_request()
    {
        $request = $this->createRequest(
            '/api/users',
            'GET',
            ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-Request-ID' => '123'],
            ['page' => 1],
            null
        );

        $route = [
            'target' => 'http://users-service',
            'methods' => ['GET'],
            'rate_limit' => 100,
            'cache_ttl' => 300,
            'auth_required' => true,
            'permissions' => ['users.read']
        ];

        $this->routeConfig->shouldReceive('getRoute')
            ->once()
            ->with('/api/users', 'GET')
            ->andReturn($route);

        $this->validator->shouldReceive('validateQueryParameters')
            ->once()
            ->with(['page' => 1], $route);

        $this->transformer->shouldReceive('transformRequest')
            ->once()
            ->with($request, $route)
            ->andReturn($request);

        $result = $this->handler->handle($request);

        $this->assertEquals($route, $result['route']);
        $this->assertEquals(['page' => 1], $result['query_params']);
        $this->assertNull($result['body']);
        $this->assertEquals($request, $result['transformed_request']);
    }

    public function test_handles_post_request_with_json_body()
    {
        $request = $this->createRequest(
            '/api/users',
            'POST',
            ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-Request-ID' => '123'],
            [],
            ['name' => 'John Doe', 'email' => 'john@example.com']
        );

        $route = [
            'target' => 'http://users-service',
            'methods' => ['POST'],
            'rate_limit' => 100,
            'cache_ttl' => 300,
            'auth_required' => true,
            'permissions' => ['users.write']
        ];

        $this->routeConfig->shouldReceive('getRoute')
            ->once()
            ->with('/api/users', 'POST')
            ->andReturn($route);

        $this->validator->shouldReceive('validateQueryParameters')
            ->once()
            ->with([], $route);

        $this->validator->shouldReceive('validateBody')
            ->once()
            ->with(['name' => 'John Doe', 'email' => 'john@example.com'], $route);

        $this->transformer->shouldReceive('transformRequest')
            ->once()
            ->with($request, $route)
            ->andReturn($request);

        $result = $this->handler->handle($request);

        $this->assertEquals($route, $result['route']);
        $this->assertEquals([], $result['query_params']);
        $this->assertEquals(['name' => 'John Doe', 'email' => 'john@example.com'], $result['body']);
        $this->assertEquals($request, $result['transformed_request']);
    }

    public function test_throws_exception_for_invalid_path()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request path');

        $request = $this->createRequest(
            '',
            'GET',
            ['Content-Type' => 'application/json', 'Accept' => 'application/json', 'X-Request-ID' => '123'],
            [],
            null
        );

        $this->handler->handle($request);
    }

    public function test_throws_exception_for_missing_required_header()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required header: Content-Type');

        $request = $this->createRequest(
            '/api/users',
            'GET',
            ['Accept' => 'application/json', 'X-Request-ID' => '123'],
            [],
            null
        );

        $this->handler->handle($request);
    }

    public function test_throws_exception_for_invalid_content_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Content-Type header');

        $request = $this->createRequest(
            '/api/users',
            'GET',
            ['Content-Type' => 'text/plain', 'Accept' => 'application/json', 'X-Request-ID' => '123'],
            [],
            null
        );

        $this->handler->handle($request);
    }

    public function test_throws_exception_for_unsupported_content_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported Content-Type');

        $request = $this->createRequest(
            '/api/users',
            'POST',
            ['Content-Type' => 'text/plain', 'Accept' => 'application/json', 'X-Request-ID' => '123'],
            [],
            'raw body'
        );

        $this->handler->handle($request);
    }

    protected function createRequest($path, $method, $headers, $query, $content)
    {
        $request = new Request();
        $request->server->set('REQUEST_URI', $path);
        $request->setMethod($method);
        
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $request->query->add($query);

        if ($content !== null) {
            if (is_array($content)) {
                $request->json = new \stdClass();
                foreach ($content as $key => $value) {
                    $request->json->$key = $value;
                }
            } else {
                $request->setContent($content);
            }
        }

        return $request;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 