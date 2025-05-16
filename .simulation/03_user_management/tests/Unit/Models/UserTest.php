<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\Permission;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_profile()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(Profile::class, $user->profile);
        $this->assertEquals($profile->id, $user->profile->id);
    }

    public function test_user_can_have_roles()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasRole($role->name));
        $this->assertFalse($user->hasRole('non-existent-role'));
    }

    public function test_user_can_have_permissions()
    {
        $user = User::factory()->create();
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission($permission->name));
        $this->assertFalse($user->hasPermission('non-existent-permission'));
    }

    public function test_user_password_is_hashed()
    {
        $user = User::factory()->create([
            'password' => 'password123'
        ]);

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_user_can_be_soft_deleted()
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
} 