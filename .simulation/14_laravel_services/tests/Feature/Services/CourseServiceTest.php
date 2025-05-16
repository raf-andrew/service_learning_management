<?php

namespace Tests\Feature\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\Section;
use App\Models\Lesson;
use App\Models\Category;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $courseService;
    protected $course;
    protected $section;
    protected $lesson;
    protected $instructor;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->course = new Course();
        $this->section = new Section();
        $this->lesson = new Lesson();
        $this->courseService = new CourseService($this->course, $this->section, $this->lesson);
        
        $this->instructor = User::factory()->create(['role' => 'instructor']);
        $this->category = Category::factory()->create();
    }

    public function test_creates_course_successfully()
    {
        Storage::fake('public');

        $data = [
            'title' => 'Test Course',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'level' => 'beginner',
            'thumbnail' => UploadedFile::fake()->image('course.jpg'),
            'price' => 99.99,
            'requirements' => ['Requirement 1', 'Requirement 2'],
            'outcomes' => ['Outcome 1', 'Outcome 2'],
        ];

        $course = $this->courseService->createCourse($data, $this->instructor);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals('Test Course', $course->title);
        $this->assertEquals('test-course', $course->slug);
        $this->assertEquals($this->instructor->id, $course->instructor_id);
        $this->assertEquals('beginner', $course->level);
        $this->assertEquals(99.99, $course->price);
        $this->assertNotNull($course->thumbnail);

        Storage::disk('public')->assertExists($course->thumbnail);
    }

    public function test_throws_exception_for_invalid_course_data()
    {
        $this->expectException(\Exception::class);

        $data = [
            'title' => 'Test Course',
            // Missing required description
            'category_id' => 999, // Invalid category
        ];

        $this->courseService->createCourse($data, $this->instructor);
    }

    public function test_enrolls_user_successfully()
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => 'published',
        ]);

        $student = User::factory()->create(['role' => 'student']);

        $result = $this->courseService->enrollUser($course, $student);

        $this->assertTrue($result);
        $this->assertTrue($course->students()->where('user_id', $student->id)->exists());
    }

    public function test_throws_exception_for_already_enrolled_user()
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => 'published',
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $course->students()->attach($student->id);

        $this->expectException(\Exception::class);
        $this->courseService->enrollUser($course, $student);
    }

    public function test_creates_section_successfully()
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
        ]);

        $data = [
            'title' => 'Test Section',
            'description' => 'Test Section Description',
            'order' => 1,
        ];

        $section = $this->courseService->createSection($course, $data);

        $this->assertInstanceOf(Section::class, $section);
        $this->assertEquals('Test Section', $section->title);
        $this->assertEquals($course->id, $section->course_id);
        $this->assertEquals(1, $section->order);
    }

    public function test_creates_lesson_successfully()
    {
        Storage::fake('public');

        $course = Course::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);

        $data = [
            'title' => 'Test Lesson',
            'description' => 'Test Lesson Description',
            'content' => 'Test Content',
            'type' => 'text',
            'duration' => 30,
            'attachments' => [
                UploadedFile::fake()->create('document.pdf', 100),
            ],
        ];

        $lesson = $this->courseService->createLesson($section, $data);

        $this->assertInstanceOf(Lesson::class, $lesson);
        $this->assertEquals('Test Lesson', $lesson->title);
        $this->assertEquals($section->id, $lesson->section_id);
        $this->assertEquals('text', $lesson->type);
        $this->assertEquals(30, $lesson->duration);
        $this->assertCount(1, $lesson->attachments);

        Storage::disk('public')->assertExists($lesson->attachments->first()->path);
    }

    public function test_updates_course_progress_successfully()
    {
        $course = Course::factory()->create();
        $student = User::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);

        // Enroll student
        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
            'status' => 'active',
            'progress' => 0,
        ]);

        $progress = $this->courseService->updateCourseProgress($course, $student, $lesson);

        $this->assertEquals(100, $progress);
        $this->assertTrue($course->students()
            ->where('user_id', $student->id)
            ->first()
            ->pivot
            ->completed_lessons()
            ->where('lesson_id', $lesson->id)
            ->exists()
        );
    }

    public function test_throws_exception_for_updating_progress_of_unenrolled_user()
    {
        $course = Course::factory()->create();
        $student = User::factory()->create();
        $section = Section::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['section_id' => $section->id]);

        $this->expectException(\Exception::class);
        $this->courseService->updateCourseProgress($course, $student, $lesson);
    }
} 