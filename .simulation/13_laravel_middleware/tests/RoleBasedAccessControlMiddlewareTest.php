<?php

namespace Tests;

use App\Http\Middleware\RoleBasedAccessControlMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\TestCase;

class RoleBasedAccessControlMiddlewareTest extends TestCase
{
    private $middleware;
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RoleBasedAccessControlMiddleware();
        $this->request = new Request();
    }

    public function testUnauthenticatedUserIsRejected()
    {
        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'user');
        
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $result->getStatusCode());
        $this->assertEquals('Unauthorized', json_decode($result->getContent())->error);
    }

    public function testUserWithRequiredRoleIsAllowed()
    {
        $user = new User();
        $user->roles = ['user'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'user');
        
        $this->assertEquals($response, $result);
        $this->assertEquals(['user'], $this->request->attributes->get('user_roles'));
    }

    public function testUserWithInheritedRoleIsAllowed()
    {
        $user = new User();
        $user->roles = ['admin'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'user');
        
        $this->assertEquals($response, $result);
        $this->assertEquals(['admin'], $this->request->attributes->get('user_roles'));
    }

    public function testUserWithoutRequiredRoleIsRejected()
    {
        $user = new User();
        $user->roles = ['user'];
        $this->request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = new Response();
        
        $next = function ($request) use ($response) {
            return $response;
        };

        $result = $this->middleware->handle($this->request, $next, 'admin');
        
        $this->assertEquals(Response::HTTP_FORBIDDEN, $result->getStatusCode());
        $this->assertEquals('Forbidden', json_decode($result->getContent())->error);
    }

    public function testUserWithAnyRequiredRoleIsAllowed()
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

        $result = $this->middleware->handle($this->request, $next, 'admin', 'manager');
        
        $this->assertEquals($response, $result);
        $this->assertEquals(['manager'], $this->request->attributes->get('user_roles'));
    }

    public function testSuperAdminHasAccessToAllRoles()
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

        $result = $this->middleware->handle($this->request, $next, 'user', 'manager', 'admin');
        
        $this->assertEquals($response, $result);
        $this->assertEquals(['super_admin'], $this->request->attributes->get('user_roles'));
    }
} 