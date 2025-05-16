<?php

namespace Tests\Feature\Forms;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createUser());
        Storage::fake('avatars');
    }

    public function test_validation_fails_with_invalid_data()
    {
        $response = $this->post('/profile/update', [
            'name' => '',
            'email' => 'not-an-email',
            'phone' => 'invalid-phone',
            'avatar' => UploadedFile::fake()->create('document.pdf', 100),
            'preferences' => [
                'theme' => 'invalid-theme',
                'language' => 'INVALID'
            ]
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors([
                'name',
                'email',
                'phone',
                'avatar',
                'preferences.theme',
                'preferences.language'
            ]);

        $this->assertEquals(
            'The name field is required.',
            session('errors')->first('name')
        );

        $this->assertEquals(
            'The email address must be a valid email address.',
            session('errors')->first('email')
        );

        $this->assertEquals(
            'Please enter a valid phone number in international format.',
            session('errors')->first('phone')
        );

        $this->assertEquals(
            'The file must be an image.',
            session('errors')->first('avatar')
        );

        $this->assertEquals(
            'Please select a valid theme.',
            session('errors')->first('preferences.theme')
        );

        $this->assertEquals(
            'Please select a valid language.',
            session('errors')->first('preferences.language')
        );
    }

    public function test_validation_passes_with_valid_data()
    {
        $response = $this->post('/profile/update', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'bio' => 'Software developer with 5 years of experience.',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'preferences' => [
                'theme' => 'dark',
                'language' => 'en',
                'notifications' => true
            ]
        ]);

        $response->assertStatus(302)
            ->assertSessionHas('success', 'Profile updated successfully');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'address' => '123 Main St',
            'bio' => 'Software developer with 5 years of experience.'
        ]);

        Storage::disk('avatars')->assertExists('avatars/' . auth()->id() . '.jpg');
    }

    public function test_validation_fails_with_duplicate_email()
    {
        // Create another user
        $otherUser = $this->createUser([
            'email' => 'other@example.com'
        ]);

        $response = $this->post('/profile/update', [
            'name' => 'John Doe',
            'email' => 'other@example.com',
            'phone' => '+1234567890'
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['email']);

        $this->assertEquals(
            'This email address is already registered.',
            session('errors')->first('email')
        );
    }

    public function test_validation_fails_with_invalid_avatar_size()
    {
        $response = $this->post('/profile/update', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => UploadedFile::fake()->image('avatar.jpg')->size(3000)
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['avatar']);

        $this->assertEquals(
            'The image size must not exceed 2048 kilobytes.',
            session('errors')->first('avatar')
        );
    }

    protected function createUser($attributes = [])
    {
        return \App\Models\User::factory()->create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ], $attributes));
    }
} 