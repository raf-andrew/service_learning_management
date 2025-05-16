<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $userRepository;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = new UserRepository(new User());
        
        // Create a test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'status' => 'active'
        ]);
    }

    public function test_creates_user_successfully()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'role' => 'student',
            'status' => 'active'
        ];

        $user = $this->userRepository->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['name'], $user->name);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertEquals($userData['role'], $user->role);
        $this->assertEquals($userData['status'], $user->status);
        $this->assertTrue(Hash::check($userData['password'], $user->password));
    }

    public function test_finds_user_by_id()
    {
        $foundUser = $this->userRepository->find($this->user->id);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->id, $foundUser->id);
        $this->assertEquals($this->user->name, $foundUser->name);
        $this->assertEquals($this->user->email, $foundUser->email);
    }

    public function test_finds_user_by_email()
    {
        $foundUser = $this->userRepository->findByEmail($this->user->email);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->id, $foundUser->id);
        $this->assertEquals($this->user->email, $foundUser->email);
    }

    public function test_updates_user_successfully()
    {
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        $result = $this->userRepository->update($this->user->id, $updateData);
        $updatedUser = $this->userRepository->find($this->user->id);

        $this->assertTrue($result);
        $this->assertEquals($updateData['name'], $updatedUser->name);
        $this->assertEquals($updateData['email'], $updatedUser->email);
    }

    public function test_deletes_user_successfully()
    {
        $result = $this->userRepository->delete($this->user->id);
        $deletedUser = $this->userRepository->find($this->user->id);

        $this->assertTrue($result);
        $this->assertNull($deletedUser);
    }

    public function test_gets_all_users_with_filters()
    {
        // Create additional test users
        User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        User::factory()->create(['role' => 'student', 'status' => 'inactive']);

        // Test filtering by role
        $teacherUsers = $this->userRepository->all(['role' => 'teacher']);
        $this->assertCount(1, $teacherUsers);
        $this->assertEquals('teacher', $teacherUsers->first()->role);

        // Test filtering by status
        $activeUsers = $this->userRepository->all(['status' => 'active']);
        $this->assertCount(2, $activeUsers);
        $this->assertEquals('active', $activeUsers->first()->status);

        // Test search functionality
        $searchResults = $this->userRepository->all(['search' => 'Test User']);
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Test User', $searchResults->first()->name);
    }

    public function test_paginates_users()
    {
        // Create additional test users
        User::factory()->count(20)->create();

        $paginatedUsers = $this->userRepository->paginate([], 10);

        $this->assertCount(10, $paginatedUsers->items());
        $this->assertEquals(3, $paginatedUsers->lastPage());
    }

    public function test_updates_user_profile()
    {
        $profileData = [
            'name' => 'Updated Profile',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'bio' => 'Test bio',
            'avatar' => 'avatar.jpg'
        ];

        $result = $this->userRepository->updateProfile($this->user->id, $profileData);
        $updatedUser = $this->userRepository->find($this->user->id);

        $this->assertTrue($result);
        $this->assertEquals($profileData['name'], $updatedUser->name);
        $this->assertEquals($profileData['phone'], $updatedUser->phone);
        $this->assertEquals($profileData['address'], $updatedUser->address);
        $this->assertEquals($profileData['bio'], $updatedUser->bio);
        $this->assertEquals($profileData['avatar'], $updatedUser->avatar);
    }

    public function test_updates_user_password()
    {
        $newPassword = 'newpassword123';
        $result = $this->userRepository->updatePassword($this->user->id, $newPassword);
        $updatedUser = $this->userRepository->find($this->user->id);

        $this->assertTrue($result);
        $this->assertTrue(Hash::check($newPassword, $updatedUser->password));
    }

    public function test_updates_user_status()
    {
        $newStatus = 'inactive';
        $result = $this->userRepository->updateStatus($this->user->id, $newStatus);
        $updatedUser = $this->userRepository->find($this->user->id);

        $this->assertTrue($result);
        $this->assertEquals($newStatus, $updatedUser->status);
    }

    public function test_throws_exception_for_invalid_user_id()
    {
        $this->expectException(\Exception::class);
        $this->userRepository->find(999);
    }
} 