<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_user_can_view_profile()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/user/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'preferences',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function test_user_can_update_profile()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/user/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully',
                'user' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_user_can_upload_avatar()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/user/profile', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'avatar',
                ]
            ]);

        Storage::disk('public')->assertExists('avatars/' . $file->hashName());
    }

    public function test_user_can_update_password()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/user/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password updated successfully'
            ]);

        $this->assertTrue(
            app('hash')->check('new-password', $this->user->fresh()->password)
        );
    }

    public function test_user_cannot_update_password_with_incorrect_current_password()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/user/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Current password is incorrect'
            ]);
    }

    public function test_user_can_update_preferences()
    {
        $preferences = [
            'notifications' => [
                'email' => true,
                'push' => false,
            ],
            'theme' => 'dark',
            'language' => 'en',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/user/preferences', [
                'preferences' => $preferences,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Preferences updated successfully',
                'preferences' => $preferences,
            ]);

        $this->assertEquals(
            $preferences,
            $this->user->fresh()->preferences
        );
    }

    public function test_user_can_delete_account()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/user/account', [
                'password' => 'password',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Account deleted successfully'
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
        ]);
    }

    public function test_user_cannot_delete_account_with_incorrect_password()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/user/account', [
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Password is incorrect'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
        ]);
    }
} 