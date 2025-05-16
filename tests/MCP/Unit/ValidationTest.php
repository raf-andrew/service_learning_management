<?php

declare(strict_types=1);

namespace MCP\Tests\Unit;

use MCP\Tests\Helpers\TestCase;
use MCP\Tests\Helpers\MockFactory;
use MCP\Validation;
use Psr\Log\LoggerInterface;

class ValidationTest extends TestCase
{
    private Validation $validation;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->logger = MockFactory::createMockLogger();
        $this->validation = new Validation($this->logger);
    }

    public function testValidationCanBeCreated(): void
    {
        $this->assertInstanceOf(Validation::class, $this->validation);
    }

    public function testValidationHasLogger(): void
    {
        $this->assertSame($this->logger, $this->validation->getLogger());
    }

    public function testValidationCanValidateRequiredFields(): void
    {
        $data = ['name' => 'Test Name'];
        $rules = ['name' => 'required'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanDetectMissingRequiredFields(): void
    {
        $data = [];
        $rules = ['name' => 'required'];
        
        $this->assertFalse($this->validation->validate($data, $rules));
        $this->assertNotEmpty($this->validation->getErrors());
    }

    public function testValidationCanValidateStringFields(): void
    {
        $data = ['name' => 'Test Name'];
        $rules = ['name' => 'string'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateIntegerFields(): void
    {
        $data = ['age' => 25];
        $rules = ['age' => 'integer'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateEmailFields(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateNumericFields(): void
    {
        $data = ['price' => '123.45'];
        $rules = ['price' => 'numeric'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateMinLength(): void
    {
        $data = ['password' => '123456'];
        $rules = ['password' => 'min:6'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateMaxLength(): void
    {
        $data = ['name' => 'Test'];
        $rules = ['name' => 'max:10'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateBetween(): void
    {
        $data = ['age' => 25];
        $rules = ['age' => 'between:18,65'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateIn(): void
    {
        $data = ['status' => 'active'];
        $rules = ['status' => 'in:active,inactive,pending'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateRegex(): void
    {
        $data = ['username' => 'test_user123'];
        $rules = ['username' => 'regex:/^[a-z0-9_]+$/'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateDate(): void
    {
        $data = ['birthday' => '1990-01-01'];
        $rules = ['birthday' => 'date'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateBefore(): void
    {
        $data = ['expiry_date' => '2023-12-31'];
        $rules = ['expiry_date' => 'before:2024-01-01'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateAfter(): void
    {
        $data = ['start_date' => '2024-01-01'];
        $rules = ['start_date' => 'after:2023-12-31'];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }

    public function testValidationCanValidateMultipleRules(): void
    {
        $data = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'age' => 25,
            'password' => '123456'
        ];
        
        $rules = [
            'name' => 'required|string|max:50',
            'email' => 'required|email',
            'age' => 'required|integer|between:18,65',
            'password' => 'required|min:6'
        ];
        
        $this->assertTrue($this->validation->validate($data, $rules));
    }
} 