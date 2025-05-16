<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_can_have_roles()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        $permission->roles()->attach($role);

        $this->assertTrue($permission->roles->contains($role));
        $this->assertEquals(1, $permission->roles->count());
    }

    public function test_permission_can_find_by_name()
    {
        $permission = Permission::factory()->create([
            'name' => 'test-permission'
        ]);

        $found = Permission::findByName('test-permission');

        $this->assertInstanceOf(Permission::class, $found);
        $this->assertEquals($permission->id, $found->id);
    }

    public function test_permission_can_find_or_create()
    {
        $permission = Permission::findOrCreate('test-permission', 'test-module');

        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('test-permission', $permission->name);
        $this->assertEquals('test-module', $permission->module);

        // Should return the same permission if called again
        $samePermission = Permission::findOrCreate('test-permission', 'test-module');
        $this->assertEquals($permission->id, $samePermission->id);
    }

    public function test_permission_can_get_users()
    {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        $user = User::factory()->create();

        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        $this->assertTrue($permission->users->contains($user));
        $this->assertEquals(1, $permission->users->count());
    }

    public function test_system_permission_cannot_be_deleted()
    {
        $permission = Permission::factory()->create(['is_system' => true]);

        $this->expectException(\Exception::class);
        $permission->delete();
    }
} 