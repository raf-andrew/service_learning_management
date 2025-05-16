<?php

namespace Tests\Feature\Api;

use App\Http\Requests\CreateCourseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createUser());
    }

    public function test_validation_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/courses', [
            'title' => '',
            'slug' => 'invalid slug',
            'description' => 'too short',
            'price' => -10,
            'category_id' => 999,
            'level' => 'invalid',
            'duration' => 0
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'title',
                    'slug',
                    'description',
                    'price',
                    'category_id',
                    'level',
                    'duration'
                ]
            ])
            ->assertJson([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'title' => [
                        'messages' => ['The course title field is required.'],
                        'field' => 'title',
                        'value' => ''
                    ],
                    'slug' => [
                        'messages' => ['The URL slug can only contain lowercase letters, numbers, and hyphens.'],
                        'field' => 'slug',
                        'value' => 'invalid slug'
                    ],
                    'description' => [
                        'messages' => ['The course description must be at least 100 characters.'],
                        'field' => 'description',
                        'value' => 'too short'
                    ],
                    'price' => [
                        'messages' => ['The course price must be at least 0.'],
                        'field' => 'price',
                        'value' => -10
                    ],
                    'category_id' => [
                        'messages' => ['The selected course category is invalid.'],
                        'field' => 'category_id',
                        'value' => 999
                    ],
                    'level' => [
                        'messages' => ['The selected course level is invalid.'],
                        'field' => 'level',
                        'value' => 'invalid'
                    ],
                    'duration' => [
                        'messages' => ['The course duration must be at least 1.'],
                        'field' => 'duration',
                        'value' => 0
                    ]
                ]
            ]);
    }

    public function test_validation_passes_with_valid_data()
    {
        $category = $this->createCategory();

        $response = $this->postJson('/api/courses', [
            'title' => 'Laravel Validation Masterclass',
            'slug' => 'laravel-validation-masterclass',
            'description' => 'Learn everything about Laravel validation, from basic rules to custom validation classes and error handling. This comprehensive course covers form requests, custom rules, validation messages, and more.',
            'price' => 99.99,
            'category_id' => $category->id,
            'level' => 'intermediate',
            'duration' => 10,
            'prerequisites' => ['Basic PHP knowledge', 'Laravel fundamentals'],
            'objectives' => ['Master form request validation', 'Create custom validation rules'],
            'max_students' => 50,
            'tags' => ['laravel', 'validation', 'php']
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Course created successfully',
                'course' => [
                    'title' => 'Laravel Validation Masterclass',
                    'slug' => 'laravel-validation-masterclass',
                    'price' => 99.99,
                    'level' => 'intermediate',
                    'duration' => 10
                ]
            ]);
    }

    public function test_validation_fails_with_duplicate_title()
    {
        $category = $this->createCategory();
        
        // Create first course
        $this->postJson('/api/courses', [
            'title' => 'Laravel Validation Masterclass',
            'slug' => 'laravel-validation-masterclass',
            'description' => 'Learn everything about Laravel validation.',
            'price' => 99.99,
            'category_id' => $category->id,
            'level' => 'intermediate',
            'duration' => 10
        ]);

        // Try to create course with same title
        $response = $this->postJson('/api/courses', [
            'title' => 'Laravel Validation Masterclass',
            'slug' => 'another-slug',
            'description' => 'Another course description.',
            'price' => 89.99,
            'category_id' => $category->id,
            'level' => 'beginner',
            'duration' => 8
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'title' => [
                        'messages' => ['A course with this title already exists.'],
                        'field' => 'title',
                        'value' => 'Laravel Validation Masterclass'
                    ]
                ]
            ]);
    }

    protected function createUser()
    {
        return \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);
    }

    protected function createCategory()
    {
        return \App\Models\Category::factory()->create([
            'name' => 'Programming',
            'slug' => 'programming'
        ]);
    }
} 