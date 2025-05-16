<?php

namespace App\Repositories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository
{
    protected $course;

    public function __construct(Course $course)
    {
        $this->course = $course;
    }

    public function create(array $data): Course
    {
        try {
            return $this->course->create($data);
        } catch (\Exception $e) {
            Log::error('Failed to create course: ' . $e->getMessage());
            throw new \Exception('Failed to create course: ' . $e->getMessage());
        }
    }

    public function find(int $id): ?Course
    {
        try {
            return $this->course->with(['instructor', 'students', 'modules'])->find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find course: ' . $e->getMessage());
            throw new \Exception('Failed to find course: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $course = $this->find($id);
            if (!$course) {
                return false;
            }

            return $course->update($data);
        } catch (\Exception $e) {
            Log::error('Failed to update course: ' . $e->getMessage());
            throw new \Exception('Failed to update course: ' . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $course = $this->find($id);
            if (!$course) {
                return false;
            }

            return $course->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete course: ' . $e->getMessage());
            throw new \Exception('Failed to delete course: ' . $e->getMessage());
        }
    }

    public function all(array $filters = []): Collection
    {
        try {
            $query = $this->course->with(['instructor', 'students']);

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['instructor_id'])) {
                $query->where('instructor_id', $filters['instructor_id']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error('Failed to get courses: ' . $e->getMessage());
            throw new \Exception('Failed to get courses: ' . $e->getMessage());
        }
    }

    public function paginate(array $filters = [], int $perPage = 15)
    {
        try {
            $query = $this->course->with(['instructor', 'students']);

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['instructor_id'])) {
                $query->where('instructor_id', $filters['instructor_id']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            return $query->paginate($perPage);
        } catch (\Exception $e) {
            Log::error('Failed to paginate courses: ' . $e->getMessage());
            throw new \Exception('Failed to paginate courses: ' . $e->getMessage());
        }
    }

    public function enrollStudent(int $courseId, int $studentId): bool
    {
        try {
            $course = $this->find($courseId);
            if (!$course) {
                return false;
            }

            $student = User::find($studentId);
            if (!$student || $student->role !== 'student') {
                return false;
            }

            // Check if student is already enrolled
            if ($course->students()->where('user_id', $studentId)->exists()) {
                return false;
            }

            $course->students()->attach($studentId, [
                'enrolled_at' => now(),
                'status' => 'active'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enroll student: ' . $e->getMessage());
            throw new \Exception('Failed to enroll student: ' . $e->getMessage());
        }
    }

    public function unenrollStudent(int $courseId, int $studentId): bool
    {
        try {
            $course = $this->find($courseId);
            if (!$course) {
                return false;
            }

            return $course->students()->detach($studentId);
        } catch (\Exception $e) {
            Log::error('Failed to unenroll student: ' . $e->getMessage());
            throw new \Exception('Failed to unenroll student: ' . $e->getMessage());
        }
    }

    public function getEnrolledStudents(int $courseId): Collection
    {
        try {
            $course = $this->find($courseId);
            if (!$course) {
                return collect();
            }

            return $course->students;
        } catch (\Exception $e) {
            Log::error('Failed to get enrolled students: ' . $e->getMessage());
            throw new \Exception('Failed to get enrolled students: ' . $e->getMessage());
        }
    }

    public function getInstructorCourses(int $instructorId): Collection
    {
        try {
            return $this->course->with(['students', 'modules'])
                ->where('instructor_id', $instructorId)
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get instructor courses: ' . $e->getMessage());
            throw new \Exception('Failed to get instructor courses: ' . $e->getMessage());
        }
    }

    public function getStudentCourses(int $studentId): Collection
    {
        try {
            return $this->course->with(['instructor', 'modules'])
                ->whereHas('students', function ($query) use ($studentId) {
                    $query->where('user_id', $studentId);
                })
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get student courses: ' . $e->getMessage());
            throw new \Exception('Failed to get student courses: ' . $e->getMessage());
        }
    }

    public function updateStatus(int $id, string $status): bool
    {
        try {
            $course = $this->find($id);
            if (!$course) {
                return false;
            }

            return $course->update(['status' => $status]);
        } catch (\Exception $e) {
            Log::error('Failed to update course status: ' . $e->getMessage());
            throw new \Exception('Failed to update course status: ' . $e->getMessage());
        }
    }

    public function getPopularCourses(int $limit = 5): Collection
    {
        try {
            return $this->course->with(['instructor'])
                ->withCount('students')
                ->orderBy('students_count', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get popular courses: ' . $e->getMessage());
            throw new \Exception('Failed to get popular courses: ' . $e->getMessage());
        }
    }
} 