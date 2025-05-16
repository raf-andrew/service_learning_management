<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Models\Role;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userService;
    protected $user;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = new User();
        $this->role = new Role();
        $this->userService = new UserService($this->user, $this->role);
    }

    public function test_registers_user_successfully()
    {
        Mail::fake();

        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $user = $this->userService->register($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotNull($user->email_verification_token);

        Mail::assertSent(\App\Mail\Welcome::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_throws_exception_for_invalid_registration_data()
    {
        $this->expectException(\Exception::class);

        $data = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        $this->userService->register($data);
    }

    public function test_authenticates_user_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $authenticatedUser = $this->userService->authenticate($credentials);

        $this->assertInstanceOf(User::class, $authenticatedUser);
        $this->assertEquals($user->id, $authenticatedUser->id);
        $this->assertNotNull($authenticatedUser->session_token);
    }

    public function test_throws_exception_for_invalid_credentials()
    {
        $this->expectException(\Exception::class);

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ];

        $this->userService->authenticate($credentials);
    }

    public function test_resets_password_successfully()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $result = $this->userService->resetPassword('test@example.com');

        $this->assertTrue($result);
        $this->assertNotNull($user->fresh()->password_reset_token);

        Mail::assertSent(\App\Mail\PasswordReset::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_updates_user_profile_successfully()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $avatar = UploadedFile::fake()->image('avatar.jpg');

        $data = [
            'name' => 'Updated Name',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'avatar' => $avatar,
        ];

        $updatedUser = $this->userService->updateProfile($user, $data);

        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertEquals('1234567890', $updatedUser->phone);
        $this->assertEquals('123 Test St', $updatedUser->address);
        $this->assertNotNull($updatedUser->avatar);

        Storage::disk('public')->assertExists($updatedUser->avatar);
    }

    public function test_updates_user_preferences_successfully()
    {
        $user = User::factory()->create();
        $preferences = [
            'theme' => 'dark',
            'notifications' => true,
            'language' => 'en',
        ];

        $updatedPreferences = $this->userService->updatePreferences($user, $preferences);

        $this->assertEquals('dark', $updatedPreferences->theme);
        $this->assertTrue($updatedPreferences->notifications);
        $this->assertEquals('en', $updatedPreferences->language);
    }

    public function test_assigns_role_successfully()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'admin']);

        $updatedUser = $this->userService->assignRole($user, 'admin');

        $this->assertTrue($updatedUser->roles->contains($role));
    }

    public function test_removes_role_successfully()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'admin']);
        $user->roles()->attach($role);

        $updatedUser = $this->userService->removeRole($user, 'admin');

        $this->assertFalse($updatedUser->roles->contains($role));
    }

    public function test_checks_role_successfully()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'admin']);
        $user->roles()->attach($role);

        $this->assertTrue($this->userService->hasRole($user, 'admin'));
        $this->assertFalse($this->userService->hasRole($user, 'user'));
    }

    public function test_checks_permission_successfully()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['name' => 'admin']);
        $permission = \App\Models\Permission::factory()->create(['name' => 'manage_users']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($this->userService->hasPermission($user, 'manage_users'));
        $this->assertFalse($this->userService->hasPermission($user, 'manage_courses'));
    }
} 