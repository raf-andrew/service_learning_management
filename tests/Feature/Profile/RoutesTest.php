<?php

namespace Tests\Feature\Profile;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Storage::fake('public');
    }

    public function test_profile_route_requires_authentication()
    {
        $response = $this->get(route('profile.show'));
        $response->assertRedirect(route('login'));
    }

    public function test_profile_route_returns_correct_view()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('profile.show'));

        $response->assertStatus(200);
        $response->assertViewIs('profile.show');
    }

    public function test_profile_route_includes_user_data()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('profile.show'));

        $response->assertViewHas('user');
        $response->assertViewHas('profile');
    }

    public function test_profile_update_route_validates_input()
    {
        $response = $this->actingAs($this->user)
                        ->put(route('profile.update'), [
                            'name' => '',
                            'email' => 'invalid-email',
                        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_profile_update_route_updates_user_data()
    {
        $newData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'bio' => 'Updated bio',
        ];

        $response = $this->actingAs($this->user)
                        ->put(route('profile.update'), $newData);

        $response->assertRedirect(route('profile.show'));
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_profile_avatar_update_route_handles_file_upload()
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($this->user)
                        ->put(route('profile.avatar'), [
                            'avatar' => $file,
                        ]);

        $response->assertRedirect(route('profile.show'));
        Storage::disk('public')->assertExists('avatars/' . $file->hashName());
    }

    public function test_profile_password_update_route_validates_current_password()
    {
        $response = $this->actingAs($this->user)
                        ->put(route('profile.password'), [
                            'current_password' => 'wrong-password',
                            'password' => 'new-password',
                            'password_confirmation' => 'new-password',
                        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_profile_password_update_route_updates_password()
    {
        $response = $this->actingAs($this->user)
                        ->put(route('profile.password'), [
                            'current_password' => 'password',
                            'password' => 'new-password',
                            'password_confirmation' => 'new-password',
                        ]);

        $response->assertRedirect(route('profile.show'));
        $this->assertTrue(
            auth()->attempt([
                'email' => $this->user->email,
                'password' => 'new-password',
            ])
        );
    }

    public function test_profile_delete_route_requires_confirmation()
    {
        $response = $this->actingAs($this->user)
                        ->delete(route('profile.destroy'));

        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    public function test_profile_delete_route_deletes_user()
    {
        $response = $this->actingAs($this->user)
                        ->delete(route('profile.destroy'), [
                            'password' => 'password',
                        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }

    public function test_profile_notification_preferences_route_updates_preferences()
    {
        $preferences = [
            'email_notifications' => true,
            'push_notifications' => false,
        ];

        $response = $this->actingAs($this->user)
                        ->put(route('profile.notifications'), $preferences);

        $response->assertRedirect(route('profile.show'));
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'email_notifications' => true,
            'push_notifications' => false,
        ]);
    }

    public function test_profile_activity_route_shows_user_activity()
    {
        $response = $this->actingAs($this->user)
                        ->get(route('profile.activity'));

        $response->assertStatus(200);
        $response->assertViewIs('profile.activity');
        $response->assertViewHas('activities');
    }
} 