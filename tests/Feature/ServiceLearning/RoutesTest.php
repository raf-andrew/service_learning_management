<?php

namespace Tests\Feature\ServiceLearning;

use Tests\Feature\BaseRouteTest;
use App\Models\User;
use App\Models\ServiceLearning;
use App\Models\Role;

class RoutesTest extends BaseRouteTest
{
    protected function shouldTestRoute($route): bool
    {
        return str_starts_with($route->uri(), 'service-learning/') || 
               str_starts_with($route->uri(), 'service_learning/');
    }

    public function test_list_service_learning_route()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'student')->first());
        $token = $user->createToken('test-token')->plainTextToken;

        ServiceLearning::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->get('/service-learning');

        $this->recordTestResult('/service-learning', 'GET', $response, [
            'user_id' => $user->id,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'status',
                        ],
                    ],
                ]);
    }

    public function test_get_service_learning_route()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'student')->first());
        $token = $user->createToken('test-token')->plainTextToken;

        $serviceLearning = ServiceLearning::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->get("/service-learning/{$serviceLearning->id}");

        $this->recordTestResult("/service-learning/{$serviceLearning->id}", 'GET', $response, [
            'user_id' => $user->id,
            'service_learning_id' => $serviceLearning->id,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'title',
                    'description',
                    'status',
                    'requirements',
                    'objectives',
                ]);
    }

    public function test_create_service_learning_route()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'faculty')->first());
        $token = $user->createToken('test-token')->plainTextToken;

        $data = [
            'title' => 'Test Service Learning',
            'description' => 'Test Description',
            'requirements' => ['Requirement 1', 'Requirement 2'],
            'objectives' => ['Objective 1', 'Objective 2'],
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->post('/service-learning', $data);

        $this->recordTestResult('/service-learning', 'POST', $response, [
            'user_id' => $user->id,
            'data' => $data,
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'id',
                    'title',
                    'description',
                    'status',
                    'requirements',
                    'objectives',
                ]);

        $this->assertDatabaseHas('service_learnings', [
            'title' => $data['title'],
            'description' => $data['description'],
        ]);
    }

    public function test_update_service_learning_route()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'faculty')->first());
        $token = $user->createToken('test-token')->plainTextToken;

        $serviceLearning = ServiceLearning::factory()->create();
        $updateData = [
            'title' => 'Updated Service Learning',
            'description' => 'Updated Description',
            'status' => 'active',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->put("/service-learning/{$serviceLearning->id}", $updateData);

        $this->recordTestResult("/service-learning/{$serviceLearning->id}", 'PUT', $response, [
            'user_id' => $user->id,
            'service_learning_id' => $serviceLearning->id,
            'update_data' => $updateData,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'title',
                    'description',
                    'status',
                ]);

        $this->assertDatabaseHas('service_learnings', $updateData);
    }

    public function test_delete_service_learning_route()
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('name', 'faculty')->first());
        $token = $user->createToken('test-token')->plainTextToken;

        $serviceLearning = ServiceLearning::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                        ->delete("/service-learning/{$serviceLearning->id}");

        $this->recordTestResult("/service-learning/{$serviceLearning->id}", 'DELETE', $response, [
            'user_id' => $user->id,
            'service_learning_id' => $serviceLearning->id,
        ]);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('service_learnings', [
            'id' => $serviceLearning->id,
        ]);
    }

    public function test_route_coverage()
    {
        $this->assertRouteCoverage();
    }
} 