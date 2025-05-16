<?php

namespace Tests;

use App\Http\Middleware\AuthorizationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuthorizationMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthorizationMiddleware();
        $this->request = new Request();
        $this->user = new \stdClass();
        $this->user->id = 1;
    }

    /** @test */
    public function it_allows_authorized_requests()
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $this->user->shouldReceive('hasRole')
            ->once()
            ->with('admin')
            ->andReturn(true);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, 'admin');

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_blocks_unauthorized_role_requests()
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $this->user->shouldReceive('hasRole')
            ->once()
            ->with('admin')
            ->andReturn(false);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, 'admin');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    /** @test */
    public function it_blocks_unauthorized_permission_requests()
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $this->user->shouldReceive('hasPermission')
            ->once()
            ->with('edit-posts')
            ->andReturn(false);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, null, 'edit-posts');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }

    /** @test */
    public function it_redirects_unauthorized_web_requests()
    {
        $this->request->headers->set('Accept', 'text/html');

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $this->user->shouldReceive('hasRole')
            ->once()
            ->with('admin')
            ->andReturn(false);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, 'admin');

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(url('/unauthorized'), $response->headers->get('Location'));
    }

    /** @test */
    public function it_logs_authorization_failures()
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        Auth::shouldReceive('id')
            ->once()
            ->andReturn(1);

        $this->user->shouldReceive('hasRole')
            ->once()
            ->with('admin')
            ->andReturn(false);

        Log::shouldReceive('warning')
            ->once()
            ->with('Authorization Failed', \Mockery::on(function ($args) {
                return isset($args['ip']) &&
                       isset($args['method']) &&
                       isset($args['url']) &&
                       isset($args['user_agent']) &&
                       isset($args['user_id']);
            }));

        $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, 'admin');
    }

    /** @test */
    public function it_allows_excluded_paths()
    {
        $this->middleware = $this->getMockBuilder(AuthorizationMiddleware::class)
            ->onlyMethods(['shouldPassThrough'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('shouldPassThrough')
            ->willReturn(true);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, 'admin');

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_checks_database_roles_when_configured()
    {
        $this->middleware = $this->getMockBuilder(AuthorizationMiddleware::class)
            ->onlyMethods(['config', 'checkDatabaseRoles'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('config')
            ->with('auth.role_provider')
            ->willReturn('database');

        $this->middleware->expects($this->once())
            ->method('checkDatabaseRoles')
            ->with($this->user, 'admin')
            ->willReturn(true);

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, 'admin');

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_checks_cache_roles_when_configured()
    {
        $this->middleware = $this->getMockBuilder(AuthorizationMiddleware::class)
            ->onlyMethods(['config', 'checkCacheRoles'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('config')
            ->with('auth.role_provider')
            ->willReturn('cache');

        $this->middleware->expects($this->once())
            ->method('checkCacheRoles')
            ->with($this->user, 'admin')
            ->willReturn(true);

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, 'admin');

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_checks_database_permissions_when_configured()
    {
        $this->middleware = $this->getMockBuilder(AuthorizationMiddleware::class)
            ->onlyMethods(['config', 'checkDatabasePermissions'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('config')
            ->with('auth.permission_provider')
            ->willReturn('database');

        $this->middleware->expects($this->once())
            ->method('checkDatabasePermissions')
            ->with($this->user, 'edit-posts')
            ->willReturn(true);

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, null, 'edit-posts');

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_checks_cache_permissions_when_configured()
    {
        $this->middleware = $this->getMockBuilder(AuthorizationMiddleware::class)
            ->onlyMethods(['config', 'checkCachePermissions'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('config')
            ->with('auth.permission_provider')
            ->willReturn('cache');

        $this->middleware->expects($this->once())
            ->method('checkCachePermissions')
            ->with($this->user, 'edit-posts')
            ->willReturn(true);

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        }, null, 'edit-posts');

        $this->assertEquals('Test Response', $response->getContent());
    }
} 