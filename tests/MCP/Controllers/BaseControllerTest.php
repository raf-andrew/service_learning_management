<?php

namespace MCP\Tests\Controllers;

use PHPUnit\Framework\TestCase;
use MCP\Controllers\BaseController;
use MCP\Presenters\BasePresenter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class BaseControllerTest extends TestCase
{
    private $controller;
    private $model;
    private $presenter;
    private $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger('test');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $this->model = new class {
            public function testMethod($params) {
                return ['test' => 'data'];
            }
        };

        $this->presenter = new BasePresenter($this->logger);

        $this->controller = new class($this->model, $this->presenter, $this->logger) extends BaseController {
            public function testMethod($params) {
                return $this->model->testMethod($params);
            }
        };
    }

    public function testHandleRequestSuccess()
    {
        $result = $this->controller->handleRequest('testMethod', ['param' => 'value']);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals(['test' => 'data'], $result['data']);
        $this->assertArrayHasKey('timestamp', $result);
    }

    public function testHandleRequestMethodNotFound()
    {
        $result = $this->controller->handleRequest('nonExistentMethod');
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Method nonExistentMethod not found', $result['error']);
    }

    public function testValidateInputSuccess()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $rules = [
            'name' => 'required',
            'email' => 'email'
        ];

        $result = $this->controller->validateInput($data, $rules);
        $this->assertTrue($result);
    }

    public function testValidateInputFailure()
    {
        $data = [
            'name' => '',
            'email' => 'invalid-email'
        ];

        $rules = [
            'name' => 'required',
            'email' => 'email'
        ];

        $result = $this->controller->validateInput($data, $rules);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
    }
} 