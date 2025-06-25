<?php

namespace Tests\Feature\User;

use Tests\Feature\BaseRouteTest;
use App\Models\User;
use App\Models\Role;

class RoutesTest extends BaseRouteTest
{
    protected function shouldTestRoute($route): bool
    {
        return str_starts_with($route->uri(), 'users/') || 
               str_starts_with($route->uri(), 'user/');
    }

    public function test_list_users_route()
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());
        $token = $admin->createToken('test-token')->plainTextToken;

        User::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->get('/users');

        $this->recordTestResult('/users', 'GET', $response, [
            'admin_id' => $admin->id,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                        ],
                    ],
                ]);
    }

    public function test_get_user_route()
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());
        $token = $admin->createToken('test-token')->plainTextToken;

        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->get("/users/{$user->id}");

        $this->recordTestResult("/users/{$user->id}", 'GET', $response, [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'name',
                    'email',
                    'roles',
                ]);
    }

    public function test_update_user_route()
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());
        $token = $admin->createToken('test-token')->plainTextToken;

        $user = User::factory()->create();
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->put("/users/{$user->id}", $updateData);

        $this->recordTestResult("/users/{$user->id}", 'PUT', $response, [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'update_data' => $updateData,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'name',
                    'email',
                ]);

        $this->assertDatabaseHas('users', $updateData);
    }

    public function test_delete_user_route()
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());
        $token = $admin->createToken('test-token')->plainTextToken;

        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->delete("/users/{$user->id}");

        $this->recordTestResult("/users/{$user->id}", 'DELETE', $response, [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
        ]);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_assign_role_route()
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->first());
        $token = $admin->createToken('test-token')->plainTextToken;

        $user = User::factory()->create();
        $role = Role::where('name', 'editor')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->post("/users/{$user->id}/roles", [
                            'role_id' => $role->id,
                        ]);

        $this->recordTestResult("/users/{$user->id}/roles", 'POST', $response, [
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('role_user', [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_route_coverage()
    {
        $this->assertRouteCoverage();
    }
} 