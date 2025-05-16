<?php

namespace Tests;

use App\Http\Middleware\CsrfProtectionMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CsrfProtectionMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CsrfProtectionMiddleware();
        $this->request = new Request();
    }

    /** @test */
    public function it_allows_read_requests_without_token()
    {
        $this->request->setMethod('GET');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_allows_requests_with_valid_token()
    {
        $token = $this->middleware->generateToken();
        $this->request->setMethod('POST');
        $this->request->headers->set('X-CSRF-TOKEN', $token);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_blocks_requests_with_invalid_token()
    {
        $this->request->setMethod('POST');
        $this->request->headers->set('X-CSRF-TOKEN', 'invalid-token');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals(419, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('CSRF token mismatch', $response->getContent());
    }

    /** @test */
    public function it_accepts_token_from_form_input()
    {
        $token = $this->middleware->generateToken();
        $this->request->setMethod('POST');
        $this->request->merge(['_token' => $token]);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_accepts_token_from_xsrf_cookie()
    {
        $token = $this->middleware->generateToken();
        $this->request->setMethod('POST');
        $this->request->headers->set('X-XSRF-TOKEN', urlencode($token));

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_allows_excluded_paths()
    {
        $this->middleware = $this->getMockBuilder(CsrfProtectionMiddleware::class)
            ->onlyMethods(['shouldPassThrough'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('shouldPassThrough')
            ->willReturn(true);

        $this->request->setMethod('POST');

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('Test Response', $response->getContent());
    }

    /** @test */
    public function it_generates_unique_tokens()
    {
        $token1 = $this->middleware->generateToken();
        $token2 = $this->middleware->generateToken();

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(40, strlen($token1));
        $this->assertEquals(40, strlen($token2));
    }

    /** @test */
    public function it_stores_token_in_session()
    {
        $token = $this->middleware->generateToken();
        $this->assertEquals($token, Session::token());
    }
} 