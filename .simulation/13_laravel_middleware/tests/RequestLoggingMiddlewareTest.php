<?php

namespace Tests;

use App\Http\Middleware\RequestLoggingMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RequestLoggingMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequestLoggingMiddleware();
        $this->request = new Request();
    }

    /** @test */
    public function it_logs_incoming_request()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Incoming Request', \Mockery::on(function ($args) {
                return isset($args['method']) &&
                       isset($args['url']) &&
                       isset($args['ip']) &&
                       isset($args['user_agent']) &&
                       isset($args['headers']) &&
                       isset($args['input']);
            }));

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_logs_completed_request()
    {
        Log::shouldReceive('info')
            ->twice() // Once for incoming, once for completed
            ->andReturnUsing(function ($message, $context) {
                if ($message === 'Request Completed') {
                    $this->assertArrayHasKey('method', $context);
                    $this->assertArrayHasKey('url', $context);
                    $this->assertArrayHasKey('status', $context);
                    $this->assertArrayHasKey('duration', $context);
                }
            });

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_sanitizes_sensitive_headers()
    {
        $this->request->headers->set('Authorization', 'Bearer secret-token');
        $this->request->headers->set('Cookie', 'session=abc123');

        Log::shouldReceive('info')
            ->once()
            ->with('Incoming Request', \Mockery::on(function ($args) {
                return $args['headers']['Authorization'] === '[REDACTED]' &&
                       $args['headers']['Cookie'] === '[REDACTED]';
            }));

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_sanitizes_sensitive_input()
    {
        $this->request->merge([
            'password' => 'secret123',
            'api_key' => 'key123',
            'normal_field' => 'value'
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Incoming Request', \Mockery::on(function ($args) {
                return $args['input']['password'] === '[REDACTED]' &&
                       $args['input']['api_key'] === '[REDACTED]' &&
                       $args['input']['normal_field'] === 'value';
            }));

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_skips_logging_for_excluded_paths()
    {
        config(['middleware.logging.excluded_paths' => ['health-check']]);
        
        $this->request->server->set('REQUEST_URI', '/health-check');

        Log::shouldReceive('info')->never();

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_skips_logging_for_excluded_methods()
    {
        config(['middleware.logging.excluded_methods' => ['OPTIONS']]);
        
        $this->request->setMethod('OPTIONS');

        Log::shouldReceive('info')->never();

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_handles_exceptions_gracefully()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Middleware Error: Test Exception', \Mockery::any());

        $response = $this->middleware->handle($this->request, function ($request) {
            throw new \Exception('Test Exception');
        });

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /** @test */
    public function it_measures_request_duration()
    {
        Log::shouldReceive('info')
            ->twice()
            ->andReturnUsing(function ($message, $context) {
                if ($message === 'Request Completed') {
                    $this->assertStringContainsString('ms', $context['duration']);
                    $this->assertIsNumeric(str_replace('ms', '', $context['duration']));
                }
            });

        $this->middleware->handle($this->request, function ($request) {
            usleep(100000); // Sleep for 100ms
            return response('Test Response');
        });
    }
} 