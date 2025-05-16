<?php

namespace Tests;

use App\Http\Middleware\SqlInjectionProtectionMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SqlInjectionProtectionMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SqlInjectionProtectionMiddleware();
        $this->request = new Request();
    }

    /** @test */
    public function it_blocks_sql_keywords_in_get_parameters()
    {
        $this->request->query->set('query', 'SELECT * FROM users');
        $this->request->query->set('id', '1 OR 1=1');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('SQL patterns', $response->getContent());
    }

    /** @test */
    public function it_blocks_sql_keywords_in_post_parameters()
    {
        $this->request->request->set('query', 'DROP TABLE users');
        $this->request->request->set('condition', '1 AND 1=1');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /** @test */
    public function it_blocks_sql_keywords_in_json_input()
    {
        $json = [
            'query' => 'UNION SELECT * FROM passwords',
            'condition' => '1 OR 1=1'
        ];

        $this->request->json = $json;
        $this->request->headers->set('Content-Type', 'application/json');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /** @test */
    public function it_blocks_sql_comments()
    {
        $this->request->request->set('query', 'SELECT * FROM users -- comment');
        $this->request->request->set('condition', '1 /* comment */ OR 1=1');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /** @test */
    public function it_blocks_sql_functions()
    {
        $this->request->request->set('query', 'SELECT COUNT(*) FROM users');
        $this->request->request->set('value', 'CONCAT(username, password)');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /** @test */
    public function it_blocks_sql_injection_techniques()
    {
        $this->request->request->set('query', 'WAITFOR DELAY \'0:0:5\'');
        $this->request->request->set('command', 'EXEC xp_cmdshell');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /** @test */
    public function it_logs_sql_injection_attempts()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('SQL Injection Attempt Detected', \Mockery::on(function ($args) {
                return isset($args['ip']) &&
                       isset($args['method']) &&
                       isset($args['url']) &&
                       isset($args['user_agent']) &&
                       isset($args['input']);
            }));

        $this->request->request->set('query', 'SELECT * FROM users');

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_sanitizes_sensitive_data_in_logs()
    {
        $this->request->request->set('query', 'SELECT * FROM users');
        $this->request->request->set('password', 'secret123');
        $this->request->request->set('api_key', 'key123');

        Log::shouldReceive('warning')
            ->once()
            ->with('SQL Injection Attempt Detected', \Mockery::on(function ($args) {
                return $args['input']['password'] === '[REDACTED]' &&
                       $args['input']['api_key'] === '[REDACTED]';
            }));

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_allows_excluded_paths()
    {
        $this->middleware = $this->getMockBuilder(SqlInjectionProtectionMiddleware::class)
            ->onlyMethods(['shouldPassThrough'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('shouldPassThrough')
            ->willReturn(true);

        $this->request->request->set('query', 'SELECT * FROM users');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_allows_safe_input()
    {
        $this->request->request->set('name', 'John Doe');
        $this->request->request->set('email', 'john@example.com');
        $this->request->request->set('age', 30);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }
} 