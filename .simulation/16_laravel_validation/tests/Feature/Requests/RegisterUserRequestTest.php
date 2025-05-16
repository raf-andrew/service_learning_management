<?php

namespace Tests\Feature\Requests;

use App\Http\Requests\RegisterUserRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterUserRequestTest extends TestCase
{
    private RegisterUserRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new RegisterUserRequest();
    }

    public function test_authorization_is_allowed()
    {
        $this->assertTrue($this->request->authorize());
    }

    public function test_validation_rules_are_correct()
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('address', $rules);
        $this->assertArrayHasKey('terms', $rules);

        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);

        $this->assertContains('required', $rules['email']);
        $this->assertContains('string', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('max:255', $rules['email']);
        $this->assertContains('unique:users', $rules['email']);

        $this->assertContains('required', $rules['password']);
        $this->assertContains('string', $rules['password']);
        $this->assertContains('confirmed', $rules['password']);

        $this->assertContains('nullable', $rules['phone']);
        $this->assertContains('string', $rules['phone']);
        $this->assertContains('max:20', $rules['phone']);

        $this->assertContains('nullable', $rules['address']);
        $this->assertContains('string', $rules['address']);
        $this->assertContains('max:255', $rules['address']);

        $this->assertContains('required', $rules['terms']);
        $this->assertContains('accepted', $rules['terms']);
    }

    public function test_custom_messages_are_correct()
    {
        $messages = $this->request->messages();

        $this->assertArrayHasKey('terms.required', $messages);
        $this->assertArrayHasKey('terms.accepted', $messages);
        $this->assertArrayHasKey('password.mixed_case', $messages);
        $this->assertArrayHasKey('password.numbers', $messages);
        $this->assertArrayHasKey('password.symbols', $messages);
        $this->assertArrayHasKey('password.uncompromised', $messages);

        $this->assertEquals('You must accept the terms and conditions.', $messages['terms.required']);
        $this->assertEquals('You must accept the terms and conditions.', $messages['terms.accepted']);
        $this->assertEquals('The password must contain both uppercase and lowercase letters.', $messages['password.mixed_case']);
        $this->assertEquals('The password must contain at least one number.', $messages['password.numbers']);
        $this->assertEquals('The password must contain at least one symbol.', $messages['password.symbols']);
        $this->assertEquals('This password has been compromised in a data breach. Please choose a different password.', $messages['password.uncompromised']);
    }

    public function test_custom_attributes_are_correct()
    {
        $attributes = $this->request->attributes();

        $this->assertArrayHasKey('terms', $attributes);
        $this->assertEquals('terms and conditions', $attributes['terms']);
    }

    public function test_validates_valid_data()
    {
        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '1234567890',
            'address' => '123 Main St',
            'terms' => true,
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validates_invalid_data()
    {
        $validator = Validator::make([
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'weak',
            'password_confirmation' => 'different',
            'phone' => str_repeat('1', 21),
            'address' => str_repeat('a', 256),
            'terms' => false,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
        $this->assertArrayHasKey('terms', $validator->errors()->toArray());
    }

    public function test_transforms_validated_data()
    {
        $validated = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '1234567890',
            'address' => '123 Main St',
            'terms' => true,
        ];

        $transformed = $this->request->transformValidatedData($validated);

        $this->assertArrayNotHasKey('password_confirmation', $transformed);
        $this->assertArrayNotHasKey('terms', $transformed);
        $this->assertArrayHasKey('name', $transformed);
        $this->assertArrayHasKey('email', $transformed);
        $this->assertArrayHasKey('password', $transformed);
        $this->assertArrayHasKey('phone', $transformed);
        $this->assertArrayHasKey('address', $transformed);
    }
} 