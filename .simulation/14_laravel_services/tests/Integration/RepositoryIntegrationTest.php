<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Repositories\UserRepository;
use App\Repositories\CourseRepository;
use App\Repositories\PaymentRepository;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $userRepository;
    protected $courseRepository;
    protected $paymentRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = app(UserRepository::class);
        $this->courseRepository = app(CourseRepository::class);
        $this->paymentRepository = app(PaymentRepository::class);
    }

    public function test_user_course_relationship()
    {
        // Create a user
        $user = $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        // Create a course
        $course = $this->courseRepository->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'instructor_id' => 1,
            'status' => 'active'
        ]);

        // Enroll user in course
        $this->courseRepository->enrollStudent($course->id, $user->id);

        // Get user's courses
        $userCourses = $this->courseRepository->getStudentCourses($user->id);
        $this->assertCount(1, $userCourses);
        $this->assertEquals($course->id, $userCourses->first()->id);

        // Get course's students
        $courseStudents = $this->courseRepository->getEnrolledStudents($course->id);
        $this->assertCount(1, $courseStudents);
        $this->assertEquals($user->id, $courseStudents->first()->id);
    }

    public function test_course_payment_relationship()
    {
        // Create a user
        $user = $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        // Create a course
        $course = $this->courseRepository->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'instructor_id' => 1,
            'status' => 'active'
        ]);

        // Create payment
        $payment = $this->paymentRepository->createPayment([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => 99.99,
            'status' => 'completed',
            'type' => 'course_purchase'
        ]);

        // Get user's payments
        $userPayments = $this->paymentRepository->getUserPayments($user->id);
        $this->assertCount(1, $userPayments);
        $this->assertEquals($payment->id, $userPayments->first()->id);

        // Get course's payments
        $coursePayments = $this->paymentRepository->getCoursePayments($course->id);
        $this->assertCount(1, $coursePayments);
        $this->assertEquals($payment->id, $coursePayments->first()->id);
    }

    public function test_payment_subscription_relationship()
    {
        // Create a user
        $user = $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        // Create a course
        $course = $this->courseRepository->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'instructor_id' => 1,
            'status' => 'active'
        ]);

        // Create subscription
        $subscription = $this->paymentRepository->createSubscription([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
            'type' => 'monthly'
        ]);

        // Get user's subscriptions
        $userSubscriptions = $this->paymentRepository->getUserSubscriptions($user->id);
        $this->assertCount(1, $userSubscriptions);
        $this->assertEquals($subscription->id, $userSubscriptions->first()->id);

        // Get course's subscriptions
        $courseSubscriptions = $this->paymentRepository->getCourseSubscriptions($course->id);
        $this->assertCount(1, $courseSubscriptions);
        $this->assertEquals($subscription->id, $courseSubscriptions->first()->id);
    }

    public function test_cascade_deletion()
    {
        // Create a user
        $user = $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        // Create a course
        $course = $this->courseRepository->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'instructor_id' => 1,
            'status' => 'active'
        ]);

        // Create payment
        $payment = $this->paymentRepository->createPayment([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => 99.99,
            'status' => 'completed',
            'type' => 'course_purchase'
        ]);

        // Create subscription
        $subscription = $this->paymentRepository->createSubscription([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
            'type' => 'monthly'
        ]);

        // Delete user
        $this->userRepository->delete($user->id);

        // Verify cascading deletion
        $this->assertNull($this->userRepository->find($user->id));
        $this->assertCount(0, $this->paymentRepository->getUserPayments($user->id));
        $this->assertCount(0, $this->paymentRepository->getUserSubscriptions($user->id));
    }

    public function test_error_handling()
    {
        // Test invalid user ID
        $this->expectException(\Exception::class);
        $this->userRepository->find(999);

        // Test invalid course ID
        $this->expectException(\Exception::class);
        $this->courseRepository->find(999);

        // Test invalid payment ID
        $this->expectException(\Exception::class);
        $this->paymentRepository->findPayment(999);
    }
} 