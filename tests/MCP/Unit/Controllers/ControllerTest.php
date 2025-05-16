<?php

declare(strict_types=1);

namespace MCP\Tests\Unit\Controllers;

use MCP\Controllers\Controller;
use MCP\Core\Config\Config;
use MCP\Core\Logger\Logger;
use MCP\Core\Validation\Validator;
use MCP\Tests\Helpers\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class TestController extends Controller
{
    public function testValidate(array $data, array $rules): bool
    {
        $_REQUEST = $data;
        return $this->validate($rules);
    }

    public function testGetValidationErrors(): array
    {
        return $this->getValidationErrors();
    }

    public function testJson(array $data, int $status = 200): void
    {
        $this->json($data, $status);
    }

    public function testError(string $message, int $status = 400): void
    {
        $this->error($message, $status);
    }

    public function testSuccess(array $data = [], int $status = 200): void
    {
        $this->success($data, $status);
    }

    public function testNotFound(string $message = 'Resource not found'): void
    {
        $this->notFound($message);
    }

    public function testUnauthorized(string $message = 'Unauthorized'): void
    {
        $this->unauthorized($message);
    }

    public function testForbidden(string $message = 'Forbidden'): void
    {
        $this->forbidden($message);
    }

    public function testServerError(string $message = 'Internal server error'): void
    {
        $this->serverError($message);
    }

    public function testLog(string $level, string $message, array $context = []): void
    {
        $this->log($level, $message, $context);
    }

    public function testGetConfig(string $key, mixed $default = null): mixed
    {
        return $this->getConfig($key, $default);
    }
}

class ControllerTest extends TestCase
{
    private TestController $controller;
    private Config|MockObject $config;
    private Logger|MockObject $logger;
    private Validator|MockObject $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(Logger::class);
        $this->validator = $this->createMock(Validator::class);

        $this->controller = new TestController(
            $this->config,
            $this->logger,
            $this->validator
        );
    }

    public function testValidateWithValidData(): void
    {
        $data = ['name' => 'Test'];
        $rules = ['name' => 'required'];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($data, $rules)
            ->willReturn([]);

        $result = $this->controller->testValidate($data, $rules);
        $this->assertTrue($result);
    }

    public function testValidateWithInvalidData(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        $errors = ['name' => ['The name field is required']];

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($data, $rules)
            ->willReturn($errors);

        $result = $this->controller->testValidate($data, $rules);
        $this->assertFalse($result);
        $this->assertEquals($errors, $this->controller->testGetValidationErrors());
    }

    public function testJsonResponse(): void
    {
        $data = ['test' => 'data'];
        $status = 200;

        $this->expectOutputString(json_encode($data));
        $this->controller->testJson($data, $status);
        $this->assertEquals($status, http_response_code());
    }

    public function testErrorResponse(): void
    {
        $message = 'Test error';
        $status = 400;
        $expected = [
            'error' => true,
            'message' => $message
        ];

        $this->expectOutputString(json_encode($expected));
        $this->controller->testError($message, $status);
        $this->assertEquals($status, http_response_code());
    }

    public function testSuccessResponse(): void
    {
        $data = ['test' => 'data'];
        $status = 200;
        $expected = [
            'error' => false,
            'data' => $data
        ];

        $this->expectOutputString(json_encode($expected));
        $this->controller->testSuccess($data, $status);
        $this->assertEquals($status, http_response_code());
    }

    public function testNotFoundResponse(): void
    {
        $message = 'Custom not found message';
        $expected = [
            'error' => true,
            'message' => $message
        ];

        $this->expectOutputString(json_encode($expected));
        $this->controller->testNotFound($message);
        $this->assertEquals(404, http_response_code());
    }

    public function testUnauthorizedResponse(): void
    {
        $message = 'Custom unauthorized message';
        $expected = [
            'error' => true,
            'message' => $message
        ];

        $this->expectOutputString(json_encode($expected));
        $this->controller->testUnauthorized($message);
        $this->assertEquals(401, http_response_code());
    }

    public function testForbiddenResponse(): void
    {
        $message = 'Custom forbidden message';
        $expected = [
            'error' => true,
            'message' => $message
        ];

        $this->expectOutputString(json_encode($expected));
        $this->controller->testForbidden($message);
        $this->assertEquals(403, http_response_code());
    }

    public function testServerErrorResponse(): void
    {
        $message = 'Custom server error message';
        $expected = [
            'error' => true,
            'message' => $message
        ];

        $this->expectOutputString(json_encode($expected));
        $this->controller->testServerError($message);
        $this->assertEquals(500, http_response_code());
    }

    public function testLogging(): void
    {
        $level = 'info';
        $message = 'Test log message';
        $context = ['test' => 'data'];

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with($level, $message, $context);

        $this->controller->testLog($level, $message, $context);
    }

    public function testGetConfig(): void
    {
        $key = 'test.key';
        $value = 'test value';

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with($key, null)
            ->willReturn($value);

        $result = $this->controller->testGetConfig($key);
        $this->assertEquals($value, $result);
    }

    public function testGetConfigWithDefault(): void
    {
        $key = 'test.key';
        $default = 'default value';

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with($key, $default)
            ->willReturn($default);

        $result = $this->controller->testGetConfig($key, $default);
        $this->assertEquals($default, $result);
    }
} 