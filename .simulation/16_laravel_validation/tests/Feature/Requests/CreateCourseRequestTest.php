<?php

namespace Tests\Feature\Requests;

use App\Http\Requests\CreateCourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateCourseRequestTest extends TestCase
{
    use RefreshDatabase;

    private CreateCourseRequest $request;
    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new CreateCourseRequest();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_authorization_is_allowed_for_authorized_user()
    {
        $this->user->givePermissionTo('create courses');
        $this->actingAs($this->user);
        $this->assertTrue($this->request->authorize());
    }

    public function test_authorization_is_denied_for_unauthorized_user()
    {
        $this->actingAs($this->user);
        $this->assertFalse($this->request->authorize());
    }

    public function test_validation_rules_are_correct()
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('title', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('category_id', $rules);
        $this->assertArrayHasKey('level', $rules);
        $this->assertArrayHasKey('duration', $rules);
        $this->assertArrayHasKey('prerequisites', $rules);
        $this->assertArrayHasKey('objectives', $rules);
        $this->assertArrayHasKey('thumbnail', $rules);
        $this->assertArrayHasKey('is_published', $rules);
        $this->assertArrayHasKey('start_date', $rules);
        $this->assertArrayHasKey('end_date', $rules);
        $this->assertArrayHasKey('max_students', $rules);
        $this->assertArrayHasKey('tags', $rules);

        $this->assertContains('required', $rules['title']);
        $this->assertContains('string', $rules['title']);
        $this->assertContains('max:255', $rules['title']);
        $this->assertContains('unique:courses', $rules['title']);

        $this->assertContains('required', $rules['slug']);
        $this->assertContains('string', $rules['slug']);
        $this->assertContains('max:255', $rules['slug']);
        $this->assertContains('unique:courses', $rules['slug']);
        $this->assertContains('regex:/^[a-z0-9-]+$/', $rules['slug']);

        $this->assertContains('required', $rules['description']);
        $this->assertContains('string', $rules['description']);
        $this->assertContains('min:100', $rules['description']);
        $this->assertContains('max:5000', $rules['description']);

        $this->assertContains('required', $rules['price']);
        $this->assertContains('numeric', $rules['price']);
        $this->assertContains('min:0', $rules['price']);
        $this->assertContains('max:9999.99', $rules['price']);

        $this->assertContains('required', $rules['category_id']);
        $this->assertContains('exists:categories,id', $rules['category_id']);

        $this->assertContains('required', $rules['level']);
        $this->assertContains('string', $rules['level']);
        $this->assertContains('in:beginner,intermediate,advanced', $rules['level']);

        $this->assertContains('required', $rules['duration']);
        $this->assertContains('integer', $rules['duration']);
        $this->assertContains('min:1', $rules['duration']);
        $this->assertContains('max:1000', $rules['duration']);

        $this->assertContains('nullable', $rules['prerequisites']);
        $this->assertContains('array', $rules['prerequisites']);

        $this->assertContains('required', $rules['objectives']);
        $this->assertContains('array', $rules['objectives']);
        $this->assertContains('min:1', $rules['objectives']);
        $this->assertContains('max:10', $rules['objectives']);

        $this->assertContains('required', $rules['thumbnail']);
        $this->assertContains('image', $rules['thumbnail']);
        $this->assertContains('max:2048', $rules['thumbnail']);

        $this->assertContains('boolean', $rules['is_published']);

        $this->assertContains('required', $rules['start_date']);
        $this->assertContains('date', $rules['start_date']);
        $this->assertContains('after:today', $rules['start_date']);

        $this->assertContains('required', $rules['end_date']);
        $this->assertContains('date', $rules['end_date']);
        $this->assertContains('after:start_date', $rules['end_date']);

        $this->assertContains('required', $rules['max_students']);
        $this->assertContains('integer', $rules['max_students']);
        $this->assertContains('min:1', $rules['max_students']);
        $this->assertContains('max:1000', $rules['max_students']);

        $this->assertContains('nullable', $rules['tags']);
        $this->assertContains('array', $rules['tags']);
    }

    public function test_custom_messages_are_correct()
    {
        $messages = $this->request->messages();

        $this->assertArrayHasKey('title.unique', $messages);
        $this->assertArrayHasKey('slug.regex', $messages);
        $this->assertArrayHasKey('description.min', $messages);
        $this->assertArrayHasKey('description.max', $messages);
        $this->assertArrayHasKey('price.min', $messages);
        $this->assertArrayHasKey('price.max', $messages);
        $this->assertArrayHasKey('level.in', $messages);
        $this->assertArrayHasKey('duration.min', $messages);
        $this->assertArrayHasKey('duration.max', $messages);
        $this->assertArrayHasKey('objectives.min', $messages);
        $this->assertArrayHasKey('objectives.max', $messages);
        $this->assertArrayHasKey('thumbnail.max', $messages);
        $this->assertArrayHasKey('start_date.after', $messages);
        $this->assertArrayHasKey('end_date.after', $messages);
        $this->assertArrayHasKey('max_students.min', $messages);
        $this->assertArrayHasKey('max_students.max', $messages);

        $this->assertEquals('A course with this title already exists.', $messages['title.unique']);
        $this->assertEquals('The slug can only contain lowercase letters, numbers, and hyphens.', $messages['slug.regex']);
        $this->assertEquals('The course description must be at least 100 characters long.', $messages['description.min']);
        $this->assertEquals('The course description cannot exceed 5000 characters.', $messages['description.max']);
        $this->assertEquals('The course price must be at least 0.', $messages['price.min']);
        $this->assertEquals('The course price cannot exceed 9999.99.', $messages['price.max']);
        $this->assertEquals('The course level must be either beginner, intermediate, or advanced.', $messages['level.in']);
        $this->assertEquals('The course duration must be at least 1 hour.', $messages['duration.min']);
        $this->assertEquals('The course duration cannot exceed 1000 hours.', $messages['duration.max']);
        $this->assertEquals('The course must have at least one learning objective.', $messages['objectives.min']);
        $this->assertEquals('The course cannot have more than 10 learning objectives.', $messages['objectives.max']);
        $this->assertEquals('The thumbnail image must not be larger than 2MB.', $messages['thumbnail.max']);
        $this->assertEquals('The course start date must be in the future.', $messages['start_date.after']);
        $this->assertEquals('The course end date must be after the start date.', $messages['end_date.after']);
        $this->assertEquals('The maximum number of students must be at least 1.', $messages['max_students.min']);
        $this->assertEquals('The maximum number of students cannot exceed 1000.', $messages['max_students.max']);
    }

    public function test_custom_attributes_are_correct()
    {
        $attributes = $this->request->attributes();

        $this->assertArrayHasKey('category_id', $attributes);
        $this->assertArrayHasKey('is_published', $attributes);
        $this->assertArrayHasKey('start_date', $attributes);
        $this->assertArrayHasKey('end_date', $attributes);
        $this->assertArrayHasKey('max_students', $attributes);

        $this->assertEquals('category', $attributes['category_id']);
        $this->assertEquals('publication status', $attributes['is_published']);
        $this->assertEquals('start date', $attributes['start_date']);
        $this->assertEquals('end date', $attributes['end_date']);
        $this->assertEquals('maximum number of students', $attributes['max_students']);
    }

    public function test_validates_valid_data()
    {
        Storage::fake('public');

        $validator = Validator::make([
            'title' => 'Introduction to Laravel',
            'slug' => 'introduction-to-laravel',
            'description' => str_repeat('a', 100),
            'price' => 99.99,
            'category_id' => $this->category->id,
            'level' => 'beginner',
            'duration' => 10,
            'prerequisites' => ['Basic PHP knowledge'],
            'objectives' => ['Learn Laravel basics'],
            'thumbnail' => UploadedFile::fake()->image('course.jpg'),
            'is_published' => true,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'max_students' => 50,
            'tags' => ['laravel', 'php', 'web-development'],
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_validates_invalid_data()
    {
        Storage::fake('public');

        $validator = Validator::make([
            'title' => '',
            'slug' => 'invalid slug',
            'description' => 'too short',
            'price' => -1,
            'category_id' => 999,
            'level' => 'invalid',
            'duration' => 0,
            'prerequisites' => 'not an array',
            'objectives' => [],
            'thumbnail' => UploadedFile::fake()->image('course.jpg')->size(3000),
            'is_published' => 'not a boolean',
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->subDays(2)->format('Y-m-d'),
            'max_students' => 0,
            'tags' => 'not an array',
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
        $this->assertArrayHasKey('description', $validator->errors()->toArray());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('level', $validator->errors()->toArray());
        $this->assertArrayHasKey('duration', $validator->errors()->toArray());
        $this->assertArrayHasKey('prerequisites', $validator->errors()->toArray());
        $this->assertArrayHasKey('objectives', $validator->errors()->toArray());
        $this->assertArrayHasKey('thumbnail', $validator->errors()->toArray());
        $this->assertArrayHasKey('is_published', $validator->errors()->toArray());
        $this->assertArrayHasKey('start_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('max_students', $validator->errors()->toArray());
        $this->assertArrayHasKey('tags', $validator->errors()->toArray());
    }

    public function test_transforms_validated_data()
    {
        $validated = [
            'title' => 'Introduction to Laravel',
            'slug' => 'INTRODUCTION-TO-LARAVEL-',
            'description' => str_repeat('a', 100),
            'price' => 99.999,
            'category_id' => $this->category->id,
            'level' => 'beginner',
            'duration' => 10,
            'prerequisites' => ['Basic PHP knowledge'],
            'objectives' => ['Learn Laravel basics', 'Learn Laravel basics'],
            'thumbnail' => UploadedFile::fake()->image('course.jpg'),
            'is_published' => true,
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'max_students' => 50,
            'tags' => ['Laravel', 'PHP', 'Web-Development', 'laravel'],
        ];

        $transformed = $this->request->transformValidatedData($validated);

        $this->assertEquals('introduction-to-laravel', $transformed['slug']);
        $this->assertEquals(100.00, $transformed['price']);
        $this->assertEquals(['laravel', 'php', 'web-development'], $transformed['tags']);
        $this->assertEquals(['Learn Laravel basics'], $transformed['objectives']);
    }

    public function test_validates_unique_title_rule()
    {
        Course::factory()->create([
            'title' => 'Introduction to Laravel',
        ]);

        $validator = Validator::make([
            'title' => 'Introduction to Laravel',
            'slug' => 'introduction-to-laravel-2',
            'description' => str_repeat('a', 100),
            'price' => 99.99,
            'category_id' => $this->category->id,
            'level' => 'beginner',
            'duration' => 10,
            'objectives' => ['Learn Laravel basics'],
            'thumbnail' => UploadedFile::fake()->image('course.jpg'),
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'max_students' => 50,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_validates_unique_slug_rule()
    {
        Course::factory()->create([
            'slug' => 'introduction-to-laravel',
        ]);

        $validator = Validator::make([
            'title' => 'Introduction to Laravel 2',
            'slug' => 'introduction-to-laravel',
            'description' => str_repeat('a', 100),
            'price' => 99.99,
            'category_id' => $this->category->id,
            'level' => 'beginner',
            'duration' => 10,
            'objectives' => ['Learn Laravel basics'],
            'thumbnail' => UploadedFile::fake()->image('course.jpg'),
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
            'max_students' => 50,
        ], $this->request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('slug', $validator->errors()->toArray());
    }
} 