<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\UserService;
use App\Services\CourseService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $userService;
    protected $courseService;
    protected $paymentService;
    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userService = app(UserService::class);
        $this->courseService = app(CourseService::class);
        $this->paymentService = app(PaymentService::class);
        $this->notificationService = app(NotificationService::class);
    }

    public function test_user_course_enrollment_flow()
    {
        // Create a user
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        // Create a course
        $course = $this->courseService->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => 1
        ]);

        // Enroll user in course
        $enrollment = $this->courseService->enrollStudent($course->id, $user->id);

        $this->assertTrue($enrollment);
        $this->assertTrue($this->courseService->isEnrolled($course->id, $user->id));
    }

    public function test_course_payment_flow()
    {
        // Create a user
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        // Create a course
        $course = $this->courseService->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => 1
        ]);

        // Process payment
        $payment = $this->paymentService->processPayment([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'payment_method' => 'card',
            'payment_intent_id' => 'test_intent_123'
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('completed', $payment->status);
        $this->assertTrue($this->courseService->isEnrolled($course->id, $user->id));
    }

    public function test_payment_notification_flow()
    {
        // Create a user
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        // Create a course
        $course = $this->courseService->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => 1
        ]);

        // Process payment
        $payment = $this->paymentService->processPayment([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'payment_method' => 'card',
            'payment_intent_id' => 'test_intent_123'
        ]);

        // Check if notification was sent
        $notification = $this->notificationService->getUserNotifications($user->id)
            ->where('type', 'payment_successful')
            ->first();

        $this->assertNotNull($notification);
        $this->assertEquals('payment_successful', $notification->type);
        $this->assertEquals($payment->id, $notification->data['payment_id']);
    }

    public function test_subscription_management_flow()
    {
        // Create a user
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        // Create a course
        $course = $this->courseService->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => 1
        ]);

        // Create subscription
        $subscription = $this->paymentService->createSubscription([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'monthly',
            'status' => 'active'
        ]);

        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);

        // Cancel subscription
        $cancelled = $this->paymentService->cancelSubscription($subscription->id);
        $this->assertTrue($cancelled);

        // Verify subscription status
        $updatedSubscription = $this->paymentService->findSubscription($subscription->id);
        $this->assertEquals('cancelled', $updatedSubscription->status);
    }

    public function test_error_handling_flow()
    {
        // Test invalid payment
        $this->expectException(\Exception::class);
        
        $this->paymentService->processPayment([
            'user_id' => 999, // Non-existent user
            'course_id' => 999, // Non-existent course
            'amount' => 99.99,
            'payment_method' => 'card',
            'payment_intent_id' => 'test_intent_123'
        ]);
    }
} 