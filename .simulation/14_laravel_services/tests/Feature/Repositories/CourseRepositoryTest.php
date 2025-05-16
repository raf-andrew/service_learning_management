<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use App\Models\Course;
use App\Models\User;
use App\Repositories\CourseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CourseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $courseRepository;
    protected $course;
    protected $instructor;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->courseRepository = new CourseRepository(new Course());
        
        // Create test instructor
        $this->instructor = User::factory()->create([
            'role' => 'instructor'
        ]);

        // Create test student
        $this->student = User::factory()->create([
            'role' => 'student'
        ]);
        
        // Create a test course
        $this->course = Course::factory()->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'category' => 'programming'
        ]);
    }

    public function test_creates_course_successfully()
    {
        $courseData = [
            'title' => 'New Course',
            'description' => 'New Description',
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'category' => 'programming'
        ];

        $course = $this->courseRepository->create($courseData);

        $this->assertInstanceOf(Course::class, $course);
        $this->assertEquals($courseData['title'], $course->title);
        $this->assertEquals($courseData['description'], $course->description);
        $this->assertEquals($courseData['instructor_id'], $course->instructor_id);
        $this->assertEquals($courseData['status'], $course->status);
        $this->assertEquals($courseData['category'], $course->category);
    }

    public function test_finds_course_by_id()
    {
        $foundCourse = $this->courseRepository->find($this->course->id);

        $this->assertInstanceOf(Course::class, $foundCourse);
        $this->assertEquals($this->course->id, $foundCourse->id);
        $this->assertEquals($this->course->title, $foundCourse->title);
        $this->assertEquals($this->course->description, $foundCourse->description);
        $this->assertNotNull($foundCourse->instructor);
    }

    public function test_updates_course_successfully()
    {
        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description'
        ];

        $result = $this->courseRepository->update($this->course->id, $updateData);
        $updatedCourse = $this->courseRepository->find($this->course->id);

        $this->assertTrue($result);
        $this->assertEquals($updateData['title'], $updatedCourse->title);
        $this->assertEquals($updateData['description'], $updatedCourse->description);
    }

    public function test_deletes_course_successfully()
    {
        $result = $this->courseRepository->delete($this->course->id);
        $deletedCourse = $this->courseRepository->find($this->course->id);

        $this->assertTrue($result);
        $this->assertNull($deletedCourse);
    }

    public function test_gets_all_courses_with_filters()
    {
        // Create additional test courses
        Course::factory()->create(['category' => 'design', 'status' => 'active']);
        Course::factory()->create(['category' => 'programming', 'status' => 'inactive']);

        // Test filtering by category
        $programmingCourses = $this->courseRepository->all(['category' => 'programming']);
        $this->assertCount(2, $programmingCourses);
        $this->assertEquals('programming', $programmingCourses->first()->category);

        // Test filtering by status
        $activeCourses = $this->courseRepository->all(['status' => 'active']);
        $this->assertCount(2, $activeCourses);
        $this->assertEquals('active', $activeCourses->first()->status);

        // Test filtering by instructor
        $instructorCourses = $this->courseRepository->all(['instructor_id' => $this->instructor->id]);
        $this->assertCount(1, $instructorCourses);
        $this->assertEquals($this->instructor->id, $instructorCourses->first()->instructor_id);

        // Test search functionality
        $searchResults = $this->courseRepository->all(['search' => 'Test Course']);
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Test Course', $searchResults->first()->title);
    }

    public function test_paginates_courses()
    {
        // Create additional test courses
        Course::factory()->count(20)->create();

        $paginatedCourses = $this->courseRepository->paginate([], 10);

        $this->assertCount(10, $paginatedCourses->items());
        $this->assertEquals(3, $paginatedCourses->lastPage());
    }

    public function test_enrolls_student_successfully()
    {
        $result = $this->courseRepository->enrollStudent($this->course->id, $this->student->id);
        $enrolledStudents = $this->courseRepository->getEnrolledStudents($this->course->id);

        $this->assertTrue($result);
        $this->assertCount(1, $enrolledStudents);
        $this->assertEquals($this->student->id, $enrolledStudents->first()->id);
    }

    public function test_prevents_duplicate_enrollment()
    {
        // First enrollment
        $this->courseRepository->enrollStudent($this->course->id, $this->student->id);
        
        // Try to enroll again
        $result = $this->courseRepository->enrollStudent($this->course->id, $this->student->id);
        
        $this->assertFalse($result);
    }

    public function test_unenrolls_student_successfully()
    {
        // First enroll the student
        $this->courseRepository->enrollStudent($this->course->id, $this->student->id);
        
        // Then unenroll
        $result = $this->courseRepository->unenrollStudent($this->course->id, $this->student->id);
        $enrolledStudents = $this->courseRepository->getEnrolledStudents($this->course->id);

        $this->assertTrue($result);
        $this->assertCount(0, $enrolledStudents);
    }

    public function test_gets_instructor_courses()
    {
        // Create additional course for the instructor
        Course::factory()->create(['instructor_id' => $this->instructor->id]);

        $instructorCourses = $this->courseRepository->getInstructorCourses($this->instructor->id);

        $this->assertCount(2, $instructorCourses);
        $this->assertEquals($this->instructor->id, $instructorCourses->first()->instructor_id);
    }

    public function test_gets_student_courses()
    {
        // Create additional course and enroll student
        $newCourse = Course::factory()->create();
        $this->courseRepository->enrollStudent($newCourse->id, $this->student->id);

        $studentCourses = $this->courseRepository->getStudentCourses($this->student->id);

        $this->assertCount(1, $studentCourses);
        $this->assertEquals($newCourse->id, $studentCourses->first()->id);
    }

    public function test_updates_course_status()
    {
        $newStatus = 'inactive';
        $result = $this->courseRepository->updateStatus($this->course->id, $newStatus);
        $updatedCourse = $this->courseRepository->find($this->course->id);

        $this->assertTrue($result);
        $this->assertEquals($newStatus, $updatedCourse->status);
    }

    public function test_gets_popular_courses()
    {
        // Create additional courses with different student counts
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();
        
        // Enroll students to create popularity
        $this->courseRepository->enrollStudent($course1->id, $this->student->id);
        $this->courseRepository->enrollStudent($course2->id, $this->student->id);
        $this->courseRepository->enrollStudent($course2->id, User::factory()->create(['role' => 'student'])->id);

        $popularCourses = $this->courseRepository->getPopularCourses(2);

        $this->assertCount(2, $popularCourses);
        $this->assertEquals($course2->id, $popularCourses->first()->id); // Course2 should be first as it has more students
    }

    public function test_throws_exception_for_invalid_course_id()
    {
        $this->expectException(\Exception::class);
        $this->courseRepository->find(999);
    }
} 