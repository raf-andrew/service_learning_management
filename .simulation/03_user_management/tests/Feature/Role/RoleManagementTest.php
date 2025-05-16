<?php

namespace Tests\Feature\Role;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->token = $this->admin->createToken('test-token')->plainTextToken;

        // Create admin role and permissions
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        $permissions = [
            'manage_roles' => Permission::create([
                'name' => 'manage_roles',
                'description' => 'Manage roles and permissions',
                'module' => 'roles',
            ]),
            'manage_users' => Permission::create([
                'name' => 'manage_users',
                'description' => 'Manage users',
                'module' => 'users',
            ]),
        ];

        $adminRole->permissions()->attach($permissions['manage_roles']);
        $this->admin->roles()->attach($adminRole);
    }

    public function test_admin_can_list_roles()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'roles' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'permissions',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    public function test_admin_can_create_role()
    {
        $permission = Permission::create([
            'name' => 'view_reports',
            'description' => 'View reports',
            'module' => 'reports',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/roles', [
                'name' => 'reporter',
                'description' => 'Can view reports',
                'permissions' => [$permission->id],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Role created successfully',
                'role' => [
                    'name' => 'reporter',
                    'description' => 'Can view reports',
                ]
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'reporter',
            'description' => 'Can view reports',
        ]);
    }

    public function test_admin_can_update_role()
    {
        $role = Role::create([
            'name' => 'editor',
            'description' => 'Content editor',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/roles/{$role->id}", [
                'name' => 'senior_editor',
                'description' => 'Senior content editor',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role updated successfully',
                'role' => [
                    'name' => 'senior_editor',
                    'description' => 'Senior content editor',
                ]
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'senior_editor',
            'description' => 'Senior content editor',
        ]);
    }

    public function test_admin_can_delete_role()
    {
        $role = Role::create([
            'name' => 'temporary',
            'description' => 'Temporary role',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role deleted successfully'
            ]);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_admin_can_assign_role_to_user()
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'editor',
            'description' => 'Content editor',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/roles/assign', [
                'user_id' => $user->id,
                'role_id' => $role->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role assigned successfully'
            ]);

        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_admin_can_remove_role_from_user()
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'editor',
            'description' => 'Content editor',
        ]);

        $user->roles()->attach($role);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/roles/remove', [
                'user_id' => $user->id,
                'role_id' => $role->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role removed successfully'
            ]);

        $this->assertFalse($user->hasRole('editor'));
    }

    public function test_admin_can_list_permissions()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/roles/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'permissions' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'module',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }
} 