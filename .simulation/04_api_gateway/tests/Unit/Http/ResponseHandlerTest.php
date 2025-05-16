<?php

namespace Tests\Unit\Http;

use Tests\TestCase;
use App\Http\ResponseHandler;
use App\Services\TransformationService;
use App\Services\CacheManager;
use App\Services\LoggingService;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Mockery;

class ResponseHandlerTest extends TestCase
{
    protected $handler;
    protected $transformer;
    protected $cacheManager;
    protected $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = Mockery::mock(TransformationService::class);
        $this->cacheManager = Mockery::mock(CacheManager::class);
        $this->logger = Mockery::mock(LoggingService::class);

        $this->handler = new ResponseHandler(
            $this->transformer,
            $this->cacheManager,
            $this->logger
        );
    }

    public function test_handles_successful_response()
    {
        $response = new Response(['data' => 'test'], 200);
        $route = [
            'cache_ttl' => 300,
            'rate_limit' => 100
        ];
        $requestData = ['path' => '/api/test'];

        $this->logger->shouldReceive('logResponse')->once();
        $this->transformer->shouldReceive('transformResponse')
            ->once()
            ->with($response, $route)
            ->andReturn($response);
        $this->cacheManager->shouldReceive('put')
            ->once()
            ->with($requestData, $response, 300);

        $result = $this->handler->handle($response, $route, $requestData);

        $this->assertEquals($response, $result);
        $this->assertEquals('public, max-age=300', $result->headers->get('Cache-Control'));
        $this->assertEquals('100', $result->headers->get('X-RateLimit-Limit'));
        $this->assertEquals('99', $result->headers->get('X-RateLimit-Remaining'));
        $this->assertEquals('nosniff', $result->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $result->headers->get('X-Frame-Options'));
    }

    public function test_handles_response_without_caching()
    {
        $response = new Response(['data' => 'test'], 200);
        $route = [
            'rate_limit' => 100
        ];
        $requestData = ['path' => '/api/test'];

        $this->logger->shouldReceive('logResponse')->once();
        $this->transformer->shouldReceive('transformResponse')
            ->once()
            ->with($response, $route)
            ->andReturn($response);

        $result = $this->handler->handle($response, $route, $requestData);

        $this->assertEquals($response, $result);
        $this->assertNull($result->headers->get('Cache-Control'));
        $this->assertEquals('100', $result->headers->get('X-RateLimit-Limit'));
    }

    public function test_handles_invalid_argument_exception()
    {
        $error = new \InvalidArgumentException('Invalid input');
        $route = [];

        $this->logger->shouldReceive('logError')->once();

        $response = $this->handler->handleError($error, $route);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Invalid input',
            'code' => Response::HTTP_BAD_REQUEST
        ], json_decode($response->getContent(), true));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_handles_not_found_exception()
    {
        $error = new NotFoundHttpException('Resource not found');
        $route = [];

        $this->logger->shouldReceive('logError')->once();

        $response = $this->handler->handleError($error, $route);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Resource not found',
            'code' => Response::HTTP_NOT_FOUND
        ], json_decode($response->getContent(), true));
    }

    public function test_handles_unauthorized_exception()
    {
        $error = new UnauthorizedHttpException('Bearer', 'Invalid token');
        $route = [];

        $this->logger->shouldReceive('logError')->once();

        $response = $this->handler->handleError($error, $route);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Invalid token',
            'code' => Response::HTTP_UNAUTHORIZED
        ], json_decode($response->getContent(), true));
    }

    public function test_handles_access_denied_exception()
    {
        $error = new AccessDeniedHttpException('Access denied');
        $route = [];

        $this->logger->shouldReceive('logError')->once();

        $response = $this->handler->handleError($error, $route);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Access denied',
            'code' => Response::HTTP_FORBIDDEN
        ], json_decode($response->getContent(), true));
    }

    public function test_handles_generic_exception()
    {
        $error = new \Exception('Unexpected error');
        $route = [];

        $this->logger->shouldReceive('logError')->once();

        $response = $this->handler->handleError($error, $route);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'An unexpected error occurred',
            'code' => Response::HTTP_INTERNAL_SERVER_ERROR
        ], json_decode($response->getContent(), true));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 