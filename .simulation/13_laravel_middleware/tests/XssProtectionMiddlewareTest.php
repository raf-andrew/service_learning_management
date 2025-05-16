<?php

namespace Tests;

use App\Http\Middleware\XssProtectionMiddleware;
use Illuminate\Http\Request;
use Tests\TestCase;

class XssProtectionMiddlewareTest extends TestCase
{
    protected $middleware;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new XssProtectionMiddleware();
        $this->request = new Request();
    }

    /** @test */
    public function it_sanitizes_get_parameters()
    {
        $this->request->query->set('name', '<script>alert("xss")</script>');
        $this->request->query->set('description', 'javascript:alert("xss")');

        $this->middleware->handle($this->request, function ($request) {
            $this->assertEquals('&lt;script&gt;alert("xss")&lt;/script&gt;', $request->query('name'));
            $this->assertEquals('javascript&#58;alert("xss")', $request->query('description'));
            return response('Test Response');
        });
    }

    /** @test */
    public function it_sanitizes_post_parameters()
    {
        $this->request->request->set('content', '<img src="x" onerror="alert(\'xss\')">');
        $this->request->request->set('url', 'javascript:void(0)');

        $this->middleware->handle($this->request, function ($request) {
            $this->assertEquals('&lt;img src="x" onerror&#61;"alert(\'xss\')"&gt;', $request->input('content'));
            $this->assertEquals('javascript&#58;void(0)', $request->input('url'));
            return response('Test Response');
        });
    }

    /** @test */
    public function it_sanitizes_json_input()
    {
        $json = [
            'title' => '<script>alert("xss")</script>',
            'nested' => [
                'content' => 'javascript:alert("xss")'
            ]
        ];

        $this->request->json = $json;
        $this->request->headers->set('Content-Type', 'application/json');

        $this->middleware->handle($this->request, function ($request) {
            $this->assertEquals('&lt;script&gt;alert("xss")&lt;/script&gt;', $request->json('title'));
            $this->assertEquals('javascript&#58;alert("xss")', $request->json('nested.content'));
            return response('Test Response');
        });
    }

    /** @test */
    public function it_adds_security_headers()
    {
        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    /** @test */
    public function it_adds_content_security_policy_when_configured()
    {
        config(['middleware.xss.content_security_policy' => "default-src 'self'"]);

        $response = $this->middleware->handle($this->request, function ($request) {
            return response('Test Response');
        });

        $this->assertEquals("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    /** @test */
    public function it_strips_html_tags_when_configured()
    {
        config(['middleware.xss.strip_tags' => true]);

        $this->request->request->set('content', '<p>Test <b>content</b></p>');

        $this->middleware->handle($this->request, function ($request) {
            $this->assertEquals('Test content', $request->input('content'));
            return response('Test Response');
        });
    }

    /** @test */
    public function it_skips_sanitization_for_excluded_paths()
    {
        $this->middleware = $this->getMockBuilder(XssProtectionMiddleware::class)
            ->onlyMethods(['shouldPassThrough'])
            ->getMock();

        $this->middleware->expects($this->once())
            ->method('shouldPassThrough')
            ->willReturn(true);

        $this->request->request->set('content', '<script>alert("xss")</script>');

        $this->middleware->handle($this->request, function ($request) {
            $this->assertEquals('<script>alert("xss")</script>', $request->input('content'));
            return response('Test Response');
        });
    }

    /** @test */
    public function it_preserves_non_string_values()
    {
        $this->request->request->set('number', 123);
        $this->request->request->set('boolean', true);
        $this->request->request->set('null', null);
        $this->request->request->set('array', ['key' => 'value']);

        $this->middleware->handle($this->request, function ($request) {
            $this->assertEquals(123, $request->input('number'));
            $this->assertEquals(true, $request->input('boolean'));
            $this->assertEquals(null, $request->input('null'));
            $this->assertEquals(['key' => 'value'], $request->input('array'));
            return response('Test Response');
        });
    }
} 