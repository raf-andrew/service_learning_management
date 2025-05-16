<?php

namespace Tests\Unit\Services;

use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Exception;

class LoggingServiceTest extends TestCase
{
    protected $loggingService;
    protected $request;
    protected $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggingService = new LoggingService();
        
        // Create a test request
        $this->request = Request::create(
            '/api/test',
            'POST',
            ['username' => 'test', 'password' => 'secret'],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer token123']
        );

        // Create a test response
        $this->response = new Response('Test response', 200);
    }

    public function test_logs_request_with_sanitized_data()
    {
        Log::shouldReceive('channel')
            ->with('api')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('API Request', [
                'method' => 'POST',
                'url' => 'http://localhost/api/test',
                'headers' => ['authorization' => '[REDACTED]'],
                'query' => [],
                'body' => [
                    'username' => 'test',
                    'password' => '[REDACTED]'
                ],
                'ip' => '127.0.0.1',
                'user_agent' => 'Symfony/3.X',
            ])
            ->once();

        $this->loggingService->logRequest($this->request);
    }

    public function test_logs_response_with_sanitized_data()
    {
        Log::shouldReceive('channel')
            ->with('api')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('API Response', [
                'method' => 'POST',
                'url' => 'http://localhost/api/test',
                'status' => 200,
                'headers' => [],
                'response_time' => \Mockery::type('float'),
            ])
            ->once();

        $this->loggingService->logResponse($this->request, $this->response);
    }

    public function test_logs_error_with_full_details()
    {
        $error = new Exception('Test error', 500);

        Log::shouldReceive('channel')
            ->with('api')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('error')
            ->with('API Error', [
                'method' => 'POST',
                'url' => 'http://localhost/api/test',
                'error' => [
                    'message' => 'Test error',
                    'code' => 500,
                    'file' => $error->getFile(),
                    'line' => $error->getLine(),
                    'trace' => $error->getTraceAsString(),
                ],
                'ip' => '127.0.0.1',
                'user_agent' => 'Symfony/3.X',
            ])
            ->once();

        $this->loggingService->logError($this->request, $error);
    }

    public function test_logs_access_with_basic_info()
    {
        Log::shouldReceive('channel')
            ->with('access')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->with('Access Log', [
                'timestamp' => \Mockery::type('string'),
                'method' => 'POST',
                'url' => 'http://localhost/api/test',
                'status' => 200,
                'ip' => '127.0.0.1',
                'user_agent' => 'Symfony/3.X',
                'response_time' => \Mockery::type('float'),
            ])
            ->once();

        $this->loggingService->logAccess($this->request, $this->response);
    }

    public function test_sanitizes_sensitive_headers()
    {
        $headers = [
            'Authorization' => 'Bearer token123',
            'Content-Type' => 'application/json',
            'X-Api-Key' => 'secret-key',
        ];

        $sanitized = $this->invokeMethod($this->loggingService, 'sanitizeHeaders', [$headers]);

        $this->assertEquals('[REDACTED]', $sanitized['Authorization']);
        $this->assertEquals('application/json', $sanitized['Content-Type']);
        $this->assertEquals('[REDACTED]', $sanitized['X-Api-Key']);
    }

    public function test_sanitizes_sensitive_params()
    {
        $params = [
            'username' => 'test',
            'password' => 'secret123',
            'token' => 'abc123',
            'data' => 'normal',
        ];

        $sanitized = $this->invokeMethod($this->loggingService, 'sanitizeParams', [$params]);

        $this->assertEquals('test', $sanitized['username']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['token']);
        $this->assertEquals('normal', $sanitized['data']);
    }

    public function test_can_add_sensitive_header()
    {
        $this->loggingService->addSensitiveHeader('custom-header');
        
        $headers = [
            'Custom-Header' => 'sensitive-data',
            'Normal-Header' => 'normal-data',
        ];

        $sanitized = $this->invokeMethod($this->loggingService, 'sanitizeHeaders', [$headers]);

        $this->assertEquals('[REDACTED]', $sanitized['Custom-Header']);
        $this->assertEquals('normal-data', $sanitized['Normal-Header']);
    }

    public function test_can_add_sensitive_param()
    {
        $this->loggingService->addSensitiveParam('custom-param');
        
        $params = [
            'custom-param' => 'sensitive-data',
            'normal-param' => 'normal-data',
        ];

        $sanitized = $this->invokeMethod($this->loggingService, 'sanitizeParams', [$params]);

        $this->assertEquals('[REDACTED]', $sanitized['custom-param']);
        $this->assertEquals('normal-data', $sanitized['normal-param']);
    }

    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
} 