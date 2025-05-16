<?php

namespace Tests\Feature\Exceptions;

use App\Exceptions\ValidationExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Illuminate\Http\Request;

class ValidationExceptionHandlerTest extends TestCase
{
    protected $handler;
    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new ValidationExceptionHandler($this->app);
        $this->request = Request::create('/test', 'POST', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    }

    public function test_validation_exception_returns_json_response()
    {
        $exception = ValidationException::withMessages([
            'email' => ['The email field is required.'],
            'password' => ['The password field is required.']
        ]);

        $response = $this->handler->render($this->request, $exception);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertJson($response->getContent());
        
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('The given data was invalid.', $content['message']);
        $this->assertEquals('error', $content['status']);
        $this->assertEquals(422, $content['code']);
    }

    public function test_validation_errors_are_formatted_correctly()
    {
        $exception = ValidationException::withMessages([
            'email' => ['The email field is required.'],
            'password' => ['The password field is required.']
        ]);

        $response = $this->handler->render($this->request, $exception);
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('errors', $content);
        $this->assertArrayHasKey('email', $content['errors']);
        $this->assertArrayHasKey('password', $content['errors']);

        $this->assertEquals([
            'messages' => ['The email field is required.'],
            'field' => 'email',
            'value' => null
        ], $content['errors']['email']);
    }

    public function test_validation_errors_are_logged()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Validation failed', \Mockery::on(function ($args) {
                return isset($args['errors']) &&
                       isset($args['input']) &&
                       isset($args['url']) &&
                       isset($args['method']);
            }));

        $exception = ValidationException::withMessages([
            'email' => ['The email field is required.']
        ]);

        $this->handler->render($this->request, $exception);
    }

    public function test_non_json_requests_are_handled_by_default_handler()
    {
        $request = Request::create('/test', 'POST');
        $exception = ValidationException::withMessages([
            'email' => ['The email field is required.']
        ]);

        $response = $this->handler->render($request, $exception);
        
        // Default Laravel validation response should be returned
        $this->assertEquals(302, $response->getStatusCode());
    }
} 