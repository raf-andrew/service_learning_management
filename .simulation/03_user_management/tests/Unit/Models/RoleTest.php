<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_can_have_users()
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $role->users()->attach($user);

        $this->assertTrue($role->users->contains($user));
        $this->assertEquals(1, $role->users->count());
    }

    public function test_role_can_have_permissions()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $role->permissions()->attach($permission);

        $this->assertTrue($role->permissions->contains($permission));
        $this->assertEquals(1, $role->permissions->count());
    }

    public function test_role_can_check_permission()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $role->permissions()->attach($permission);

        $this->assertTrue($role->hasPermission($permission->name));
        $this->assertFalse($role->hasPermission('non-existent-permission'));
    }

    public function test_role_can_give_permission()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermission($permission->name));
    }

    public function test_role_can_revoke_permission()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $role->permissions()->attach($permission);

        $role->revokePermissionTo($permission);

        $this->assertFalse($role->hasPermission($permission->name));
    }

    public function test_system_role_cannot_be_deleted()
    {
        $role = Role::factory()->create(['is_system' => true]);

        $this->expectException(\Exception::class);
        $role->delete();
    }
} 