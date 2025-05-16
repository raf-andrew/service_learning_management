<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\Section;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseService
{
    protected $course;
    protected $section;
    protected $lesson;

    public function __construct(Course $course, Section $section, Lesson $lesson)
    {
        $this->course = $course;
        $this->section = $section;
        $this->lesson = $lesson;
    }

    public function createCourse(array $data, User $instructor)
    {
        // Validate course data
        $this->validateCourseData($data);

        // Create course record
        $course = $this->course->create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'description' => $data['description'],
            'instructor_id' => $instructor->id,
            'category_id' => $data['category_id'],
            'level' => $data['level'] ?? 'beginner',
            'status' => $data['status'] ?? 'draft',
            'thumbnail' => $this->handleThumbnail($data['thumbnail'] ?? null),
            'price' => $data['price'] ?? 0,
            'discount_price' => $data['discount_price'] ?? null,
            'requirements' => $data['requirements'] ?? [],
            'outcomes' => $data['outcomes'] ?? [],
            'language' => $data['language'] ?? 'en',
        ]);

        // Create initial section if provided
        if (isset($data['initial_section'])) {
            $this->createSection($course, $data['initial_section']);
        }

        return $course;
    }

    public function enrollUser(Course $course, User $user)
    {
        // Check if user is already enrolled
        if ($course->students()->where('user_id', $user->id)->exists()) {
            throw new \Exception('User is already enrolled in this course');
        }

        // Check if course is available for enrollment
        if ($course->status !== 'published') {
            throw new \Exception('Course is not available for enrollment');
        }

        // Create enrollment record
        $course->students()->attach($user->id, [
            'enrolled_at' => now(),
            'status' => 'active',
            'progress' => 0,
        ]);

        return true;
    }

    public function createSection(Course $course, array $data)
    {
        // Validate section data
        $this->validateSectionData($data);

        // Create section
        $section = $this->section->create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'order' => $data['order'] ?? $course->sections()->count() + 1,
        ]);

        // Create lessons if provided
        if (isset($data['lessons'])) {
            foreach ($data['lessons'] as $lessonData) {
                $this->createLesson($section, $lessonData);
            }
        }

        return $section;
    }

    public function createLesson(Section $section, array $data)
    {
        // Validate lesson data
        $this->validateLessonData($data);

        // Create lesson
        $lesson = $this->lesson->create([
            'section_id' => $section->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'content' => $data['content'],
            'type' => $data['type'] ?? 'text',
            'duration' => $data['duration'] ?? 0,
            'order' => $data['order'] ?? $section->lessons()->count() + 1,
            'is_free' => $data['is_free'] ?? false,
            'status' => $data['status'] ?? 'draft',
        ]);

        // Handle lesson attachments
        if (isset($data['attachments'])) {
            $this->handleLessonAttachments($lesson, $data['attachments']);
        }

        return $lesson;
    }

    public function updateCourseProgress(Course $course, User $user, Lesson $lesson)
    {
        $enrollment = $course->students()->where('user_id', $user->id)->first();

        if (!$enrollment) {
            throw new \Exception('User is not enrolled in this course');
        }

        // Mark lesson as completed
        $enrollment->pivot->completed_lessons()->attach($lesson->id, [
            'completed_at' => now(),
        ]);

        // Calculate and update overall progress
        $totalLessons = $course->sections()
            ->withCount('lessons')
            ->get()
            ->sum('lessons_count');

        $completedLessons = $enrollment->pivot->completed_lessons()->count();
        $progress = ($completedLessons / $totalLessons) * 100;

        $enrollment->pivot->update([
            'progress' => $progress,
        ]);

        return $progress;
    }

    protected function validateCourseData(array $data)
    {
        $validator = \Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'level' => 'sometimes|in:beginner,intermediate,advanced',
            'status' => 'sometimes|in:draft,published,archived',
            'thumbnail' => 'sometimes|image|max:2048',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'sometimes|numeric|min:0|lt:price',
            'requirements' => 'sometimes|array',
            'outcomes' => 'sometimes|array',
            'language' => 'sometimes|string|size:2',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    protected function validateSectionData(array $data)
    {
        $validator = \Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'order' => 'sometimes|integer|min:1',
            'lessons' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    protected function validateLessonData(array $data)
    {
        $validator = \Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'content' => 'required|string',
            'type' => 'sometimes|in:text,video,quiz,assignment',
            'duration' => 'sometimes|integer|min:0',
            'order' => 'sometimes|integer|min:1',
            'is_free' => 'sometimes|boolean',
            'status' => 'sometimes|in:draft,published',
            'attachments' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    protected function handleThumbnail($thumbnail)
    {
        if (!$thumbnail) {
            return null;
        }

        $path = $thumbnail->store('course-thumbnails', 'public');
        return $path;
    }

    protected function handleLessonAttachments(Lesson $lesson, array $attachments)
    {
        foreach ($attachments as $attachment) {
            $path = $attachment->store('lesson-attachments', 'public');
            $lesson->attachments()->create([
                'filename' => $attachment->getClientOriginalName(),
                'path' => $path,
                'size' => $attachment->getSize(),
                'type' => $attachment->getMimeType(),
            ]);
        }
    }
} 