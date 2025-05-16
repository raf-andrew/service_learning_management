<?php

namespace Tests;

use App\Http\Middleware\AuthenticationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuthenticationMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthenticationMiddleware();
        $this->request = new Request();
    }

    /** @test */
    public function it_allows_authenticated_requests()
    {
        $user = new \stdClass();
        $user->id = 1;

        Auth::shouldReceive('guard')
            ->once()
            ->with('web')
            ->andReturnSelf();

        Auth::shouldReceive('check')
            ->once()
            ->andReturn(true);

        Auth::shouldReceive('shouldUse')
            ->once()
            ->with('web');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_blocks_unauthenticated_requests()
    {
        Auth::shouldReceive('guard')
            ->once()
            ->with('web')
            ->andReturnSelf();

        Auth::shouldReceive('check')
            ->once()
            ->andReturn(false);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Unauthenticated', $response->getContent());
    }

    /** @test */
    public function it_redirects_unauthenticated_web_requests()
    {
        $this->request->headers->set('Accept', 'text/html');

        Auth::shouldReceive('guard')
            ->once()
            ->with('web')
            ->andReturnSelf();

        Auth::shouldReceive('check')
            ->once()
            ->andReturn(false);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(url('login'), $response->headers->get('Location'));
    }

    /** @test */
    public function it_logs_authentication_failures()
    {
        Auth::shouldReceive('guard')
            ->once()
            ->with('web')
            ->andReturnSelf();

        Auth::shouldReceive('check')
            ->once()
            ->andReturn(false);

        Auth::shouldReceive('getDefaultDriver')
            ->once()
            ->andReturn('web');

        Log::shouldReceive('warning')
            ->once()
            ->with('Authentication Failed', \Mockery::on(function ($args) {
                return isset($args['ip']) &&
                       isset($args['method']) &&
                       isset($args['url']) &&
                       isset($args['user_agent']) &&
                       isset($args['attempted_guard']);
            }));

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });
    }

    /** @test */
    public function it_allows_excluded_paths()
    {
        $this->middleware = $this->getMockBuilder(AuthenticationMiddleware::class)
            ->onlyMethods(['shouldPassThrough'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('shouldPassThrough')
            ->willReturn(true);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_supports_multiple_guards()
    {
        $this->middleware = $this->getMockBuilder(AuthenticationMiddleware::class)
            ->onlyMethods(['config'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('config')
            ->with('auth.guards')
            ->willReturn(['web', 'api']);

        Auth::shouldReceive('guard')
            ->once()
            ->with('web')
            ->andReturnSelf();

        Auth::shouldReceive('check')
            ->once()
            ->andReturn(false);

        Auth::shouldReceive('guard')
            ->once()
            ->with('api')
            ->andReturnSelf();

        Auth::shouldReceive('check')
            ->once()
            ->andReturn(true);

        Auth::shouldReceive('shouldUse')
            ->once()
            ->with('api');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_returns_user_from_auth_facade()
    {
        $user = new \stdClass();
        $user->id = 1;

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->assertEquals($user, $this->middleware->user());
    }

    /** @test */
    public function it_returns_default_guard()
    {
        Auth::shouldReceive('getDefaultDriver')
            ->once()
            ->andReturn('web');

        $this->assertEquals('web', $this->middleware->guard());
    }
} 