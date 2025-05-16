<?php

namespace App\Exceptions;

class CourseServiceException extends ServiceException
{
    public const COURSE_NOT_FOUND = 2001;
    public const INVALID_INSTRUCTOR = 2002;
    public const ENROLLMENT_FAILED = 2003;
    public const COURSE_FULL = 2004;
    public const INVALID_PRICE = 2005;
    public const DUPLICATE_COURSE = 2006;

    public static function courseNotFound(int $courseId): self
    {
        return new self(
            "Course with ID {$courseId} not found",
            self::COURSE_NOT_FOUND,
            ['course_id' => $courseId]
        );
    }

    public static function invalidInstructor(int $instructorId): self
    {
        return new self(
            "Invalid instructor with ID {$instructorId}",
            self::INVALID_INSTRUCTOR,
            ['instructor_id' => $instructorId]
        );
    }

    public static function enrollmentFailed(int $userId, int $courseId): self
    {
        return new self(
            "Failed to enroll user {$userId} in course {$courseId}",
            self::ENROLLMENT_FAILED,
            [
                'user_id' => $userId,
                'course_id' => $courseId
            ]
        );
    }

    public static function courseFull(int $courseId): self
    {
        return new self(
            "Course {$courseId} has reached maximum capacity",
            self::COURSE_FULL,
            ['course_id' => $courseId]
        );
    }

    public static function invalidPrice(float $price): self
    {
        return new self(
            "Invalid price: {$price}",
            self::INVALID_PRICE,
            ['price' => $price]
        );
    }

    public static function duplicateCourse(string $title): self
    {
        return new self(
            "Course with title '{$title}' already exists",
            self::DUPLICATE_COURSE,
            ['title' => $title]
        );
    }
} 