<?php

namespace Tests\Feature\Validation;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationMessagesTest extends TestCase
{
    public function test_default_validation_messages_are_loaded()
    {
        $validator = Validator::make(
            ['email' => 'not-an-email'],
            ['email' => 'email']
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The email address must be a valid email address.',
            $validator->errors()->first('email')
        );
    }

    public function test_custom_validation_messages_are_loaded()
    {
        $validator = Validator::make(
            ['email' => 'test@example.com'],
            ['email' => 'unique:users,email']
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'This email address is already registered.',
            $validator->errors()->first('email')
        );
    }

    public function test_custom_attribute_names_are_used()
    {
        $validator = Validator::make(
            ['bio' => ''],
            ['bio' => 'required']
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'The biography field is required.',
            $validator->errors()->first('bio')
        );
    }

    public function test_nested_attribute_names_are_used()
    {
        $validator = Validator::make(
            ['preferences' => ['theme' => 'invalid']],
            ['preferences.theme' => 'in:light,dark']
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Please select a valid theme.',
            $validator->errors()->first('preferences.theme')
        );
    }

    public function test_custom_messages_for_course_validation()
    {
        $validator = Validator::make(
            ['course' => ['title' => 'Test Course']],
            ['course.title' => 'unique:courses,title']
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'A course with this title already exists.',
            $validator->errors()->first('course.title')
        );
    }

    public function test_custom_messages_for_payment_validation()
    {
        $validator = Validator::make(
            ['card_number' => '1234'],
            ['card_number' => 'regex:/^[0-9]{16}$/']
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Please enter a valid card number.',
            $validator->errors()->first('card_number')
        );
    }

    public function test_custom_messages_with_parameters()
    {
        $validator = Validator::make(
            ['password' => 'short'],
            ['password' => 'min:8']
        );

        $this->assertTrue($validator->fails());
        $this->assertEquals(
            'Your password must be at least 8 characters long.',
            $validator->errors()->first('password')
        );
    }
} 