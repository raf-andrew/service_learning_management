<?php

namespace Tests\Feature\Requests;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    private UpdateProfileRequest $request;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateProfileRequest();
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
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
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('address', $rules);
        $this->assertArrayHasKey('bio', $rules);
        $this->assertArrayHasKey('avatar', $rules);
        $this->assertArrayHasKey('preferences', $rules);
        $this->assertArrayHasKey('preferences.notifications', $rules);
        $this->assertArrayHasKey('preferences.theme', $rules);
        $this->assertArrayHasKey('preferences.language', $rules);

        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);

        $this->assertContains('required', $rules['email']);
        $this->assertContains('string', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('max:255', $rules['email']);

        $this->assertContains('nullable', $rules['phone']);
        $this->assertContains('string', $rules['phone']);
        $this->assertContains('regex:/^\+?[1-9]\d{1,14}$/', $rules['phone']);

        $this->assertContains('nullable', $rules['address']);
        $this->assertContains('string', $rules['address']);
        $this->assertContains('max:255', $rules['address']);

        $this->assertContains('nullable', $rules['bio']);
        $this->assertContains('string', $rules['bio']);
        $this->assertContains('max:1000', $rules['bio']);

        $this->assertContains('nullable', $rules['avatar']);
        $this->assertContains('image', $rules['avatar']);
        $this->assertContains('max:2048', $rules['avatar']);

        $this->assertContains('nullable', $rules['preferences']);
        $this->assertContains('array', $rules['preferences']);

        $this->assertContains('nullable', $rules['preferences.notifications']);
        $this->assertContains('boolean', $rules['preferences.notifications']);

        $this->assertContains('nullable', $rules['preferences.theme']);
        $this->assertContains('string', $rules['preferences.theme']);
        $this->assertContains('in:light,dark,system', $rules['preferences.theme']);

        $this->assertContains('nullable', $rules['preferences.language']);
        $this->assertContains('string', $rules['preferences.language']);
        $this->assertContains('size:2', $rules['preferences.language']);
    }

    public function test_custom_messages_are_correct()
    {
        $messages = $this->request->messages();

        $this->assertArrayHasKey('phone.regex', $messages);
        $this->assertArrayHasKey('avatar.max', $messages);
        $this->assertArrayHasKey('preferences.theme.in', $messages);
        $this->assertArrayHasKey('preferences.language.size', $messages);

        $this->assertEquals('The phone number must be in a valid international format.', $messages['phone.regex']);
        $this->assertEquals('The avatar image must not be larger than 2MB.', $messages['avatar.max']);
        $this->assertEquals('The theme must be either light, dark, or system.', $messages['preferences.theme.in']);
        $this->assertEquals('The language code must be exactly 2 characters.', $messages['preferences.language.size']);
    }

    public function test_custom_attributes_are_correct()
    {
        $attributes = $this->request->attributes();

        $this->assertArrayHasKey('bio', $attributes);
        $this->assertArrayHasKey('preferences.notifications', $attributes);
        $this->assertArrayHasKey('preferences.theme', $attributes);
        $this->assertArrayHasKey('preferences.language', $attributes);

        $this->assertEquals('biography', $attributes['bio']);
        $this->assertEquals('notification preferences', $attributes['preferences.notifications']);
        $this->assertEquals('theme preference', $attributes['preferences.theme']);
        $this->assertEquals('language preference', $attributes['preferences.language']);
    }

    public function test_validates_valid_data()
    {
        Storage::fake('public');

        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'bio' => 'Software developer',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'preferences' => [
                'notifications' => true,
                'theme' => 'dark',
                'language' => 'en',
            ],
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validates_invalid_data()
    {
        Storage::fake('public');

        $validator = Validator::make([
            'name' => '',
            'email' => 'invalid-email',
            'phone' => 'invalid-phone',
            'address' => str_repeat('a', 256),
            'bio' => str_repeat('a', 1001),
            'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000),
            'preferences' => [
                'notifications' => 'not-a-boolean',
                'theme' => 'invalid-theme',
                'language' => 'invalid',
            ],
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
        $this->assertArrayHasKey('address', $validator->errors()->toArray());
        $this->assertArrayHasKey('bio', $validator->errors()->toArray());
        $this->assertArrayHasKey('avatar', $validator->errors()->toArray());
        $this->assertArrayHasKey('preferences.notifications', $validator->errors()->toArray());
        $this->assertArrayHasKey('preferences.theme', $validator->errors()->toArray());
        $this->assertArrayHasKey('preferences.language', $validator->errors()->toArray());
    }

    public function test_transforms_validated_data()
    {
        $validated = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'address' => '123 Main St',
            'bio' => 'Software developer',
            'preferences' => [
                'notifications' => true,
                'theme' => 'dark',
                'language' => 'EN',
            ],
        ];

        $transformed = $this->request->transformValidatedData($validated);

        $this->assertEquals('+1234567890', $transformed['phone']);
        $this->assertEquals('en', $transformed['preferences']['language']);
        $this->assertEquals('John Doe', $transformed['name']);
        $this->assertEquals('john@example.com', $transformed['email']);
        $this->assertEquals('123 Main St', $transformed['address']);
        $this->assertEquals('Software developer', $transformed['bio']);
        $this->assertTrue($transformed['preferences']['notifications']);
        $this->assertEquals('dark', $transformed['preferences']['theme']);
    }

    public function test_validates_unique_email_rule()
    {
        $validator = Validator::make([
            'name' => 'John Doe',
            'email' => 'test@example.com', // Same as existing user
            'phone' => '+1234567890',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }
} 