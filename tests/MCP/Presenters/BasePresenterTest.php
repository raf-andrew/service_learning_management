<?php

namespace MCP\Tests\Presenters;

use PHPUnit\Framework\TestCase;
use MCP\Presenters\BasePresenter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class BasePresenterTest extends TestCase
{
    private $presenter;
    private $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        $this->presenter = new BasePresenter($this->logger);
    }

    public function testFormatResponse()
    {
        $data = ['test' => 'data'];
        $result = $this->presenter->formatResponse($data);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals($data, $result['data']);
        $this->assertArrayHasKey('timestamp', $result);
    }

    public function testFormatError()
    {
        $message = 'Test error message';
        $result = $this->presenter->formatError($message);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals($message, $result['error']);
        $this->assertArrayHasKey('timestamp', $result);
    }

    public function testFormatValidationError()
    {
        $errors = [
            'name' => 'Invalid name',
            'email' => 'Invalid email'
        ];

        $result = $this->presenter->formatValidationError($errors);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Validation failed', $result['error']);
        $this->assertEquals($errors, $result['errors']);
        $this->assertArrayHasKey('timestamp', $result);
    }

    public function testFormatData()
    {
        $data = [
            'name' => 'Test User',
            'details' => [
                'age' => 25,
                'email' => 'test@example.com'
            ]
        ];

        $result = $this->presenter->formatData($data);

        $this->assertEquals($data, $result);
    }
} 