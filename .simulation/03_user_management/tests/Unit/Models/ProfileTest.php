<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_profile_belongs_to_user()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $profile->user);
        $this->assertEquals($user->id, $profile->user->id);
    }

    public function test_profile_can_get_full_address()
    {
        $profile = Profile::factory()->create([
            'address' => '123 Main St',
            'city' => 'Test City',
            'state' => 'Test State',
            'postal_code' => '12345',
            'country' => 'Test Country'
        ]);

        $expected = '123 Main St, Test City, Test State, 12345, Test Country';
        $this->assertEquals($expected, $profile->full_address);
    }

    public function test_profile_can_update_avatar()
    {
        $profile = Profile::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $profile->updateAvatar($file->store('avatars', 'public'));

        $this->assertNotNull($profile->avatar);
        Storage::disk('public')->assertExists($profile->avatar);
    }

    public function test_profile_gets_gravatar_url_when_no_avatar()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com'
        ]);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $expected = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp';
        $this->assertEquals($expected, $profile->avatar_url);
    }

    public function test_profile_preferences_are_cast_to_array()
    {
        $preferences = [
            'theme' => 'dark',
            'notifications' => true
        ];

        $profile = Profile::factory()->create([
            'preferences' => $preferences
        ]);

        $this->assertIsArray($profile->preferences);
        $this->assertEquals($preferences, $profile->preferences);
    }

    public function test_profile_avatar_url_returns_storage_url_when_avatar_exists()
    {
        $profile = Profile::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');
        $path = $file->store('avatars', 'public');

        $profile->updateAvatar($path);

        $this->assertEquals(Storage::url($path), $profile->avatar_url);
    }
} 