<?php

namespace Tests;

use App\Http\Middleware\PermissionBasedAccessControlMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class PermissionBasedAccessControlMiddlewareTest extends TestCase
{
    private $middleware;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new PermissionBasedAccessControlMiddleware();
        $this->request = new Request();
    }

    public function testUnauthenticatedUserIsRejected()
    {
        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'view_content');
        
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $result->getStatusCode());
        $this->assertEquals('Unauthorized', json_decode($result->getContent())->error);
    }

    public function testUserWithRequiredPermissionIsAllowed()
    {
        $user = new User();
        $user->permissions = ['view_content'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'view_content');
        
        $this->assertEquals($response, $result);
        $this->assertEquals(['view_content'], $this->request->attributes->get('user_permissions'));
    }

    public function testUserWithRolePermissionIsAllowed()
    {
        $user = new User();
        $user->roles = ['manager'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'view_reports');
        
        $this->assertEquals($response, $result);
    }

    public function testUserWithoutRequiredPermissionIsRejected()
    {
        $user = new User();
        $user->permissions = ['view_content'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'manage_users');
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
        $this->assertEquals('Forbidden', json_decode($result->getContent())->error);
    }

    public function testUserNeedsAllRequiredPermissions()
    {
        $user = new User();
        $user->permissions = ['view_content'];
        $user->roles = ['manager'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'view_content', 'manage_users');
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
        $this->assertEquals('Forbidden', json_decode($result->getContent())->error);
    }

    public function testSuperAdminHasAllPermissions()
    {
        $user = new User();
        $user->roles = ['super_admin'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'manage_users', 'manage_roles', 'manage_permissions');
        
        $this->assertEquals($response, $result);
    }

    public function testDirectPermissionOverridesRolePermission()
    {
        $user = new User();
        $user->roles = ['user'];
        $user->permissions = ['manage_users'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'manage_users');
        
        $this->assertEquals($response, $result);
        $this->assertEquals(['manage_users'], $this->request->attributes->get('user_permissions'));
    }
} 