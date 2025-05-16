<?php

namespace Tests\Feature\Services;

use App\Exceptions\CourseServiceException;
use App\Exceptions\NotificationServiceException;
use App\Exceptions\PaymentServiceException;
use App\Exceptions\ServiceException;
use App\Exceptions\UserServiceException;
use App\Services\CourseService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;
    private CourseService $courseService;
    private PaymentService $paymentService;
    private NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
        $this->courseService = app(CourseService::class);
        $this->paymentService = app(PaymentService::class);
        $this->notificationService = app(NotificationService::class);
    }

    public function test_user_service_exceptions()
    {
        // Test user not found
        $this->expectException(UserServiceException::class);
        $this->expectExceptionCode(UserServiceException::USER_NOT_FOUND);
        $this->userService->getUser(999);

        // Test invalid credentials
        $this->expectException(UserServiceException::class);
        $this->expectExceptionCode(UserServiceException::INVALID_CREDENTIALS);
        $this->userService->authenticate('invalid@email.com', 'wrongpassword');

        // Test email already exists
        $user = $this->userService->createUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'student'
        ]);

        $this->expectException(UserServiceException::class);
        $this->expectExceptionCode(UserServiceException::EMAIL_ALREADY_EXISTS);
        $this->userService->createUser([
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => 'student'
        ]);
    }

    public function test_course_service_exceptions()
    {
        // Test course not found
        $this->expectException(CourseServiceException::class);
        $this->expectExceptionCode(CourseServiceException::COURSE_NOT_FOUND);
        $this->courseService->getCourse(999);

        // Test invalid instructor
        $this->expectException(CourseServiceException::class);
        $this->expectExceptionCode(CourseServiceException::INVALID_INSTRUCTOR);
        $this->courseService->createCourse([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => 999
        ]);

        // Test invalid price
        $this->expectException(CourseServiceException::class);
        $this->expectExceptionCode(CourseServiceException::INVALID_PRICE);
        $this->courseService->createCourse([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => -50.00,
            'instructor_id' => 1
        ]);
    }

    public function test_payment_service_exceptions()
    {
        // Test payment not found
        $this->expectException(PaymentServiceException::class);
        $this->expectExceptionCode(PaymentServiceException::PAYMENT_NOT_FOUND);
        $this->paymentService->getPayment(999);

        // Test invalid amount
        $this->expectException(PaymentServiceException::class);
        $this->expectExceptionCode(PaymentServiceException::INVALID_AMOUNT);
        $this->paymentService->processPayment([
            'user_id' => 1,
            'course_id' => 1,
            'amount' => -50.00,
            'payment_method' => 'credit_card'
        ]);

        // Test invalid payment method
        $this->expectException(PaymentServiceException::class);
        $this->expectExceptionCode(PaymentServiceException::INVALID_PAYMENT_METHOD);
        $this->paymentService->processPayment([
            'user_id' => 1,
            'course_id' => 1,
            'amount' => 50.00,
            'payment_method' => 'invalid_method'
        ]);
    }

    public function test_notification_service_exceptions()
    {
        // Test invalid recipient
        $this->expectException(NotificationServiceException::class);
        $this->expectExceptionCode(NotificationServiceException::INVALID_RECIPIENT);
        $this->notificationService->sendNotification([
            'user_id' => 999,
            'type' => 'email',
            'message' => 'Test message'
        ]);

        // Test invalid notification type
        $this->expectException(NotificationServiceException::class);
        $this->expectExceptionCode(NotificationServiceException::INVALID_NOTIFICATION_TYPE);
        $this->notificationService->sendNotification([
            'user_id' => 1,
            'type' => 'invalid_type',
            'message' => 'Test message'
        ]);

        // Test invalid template
        $this->expectException(NotificationServiceException::class);
        $this->expectExceptionCode(NotificationServiceException::INVALID_TEMPLATE);
        $this->notificationService->sendEmailNotification(1, 'invalid_template', []);
    }

    public function test_exception_context()
    {
        try {
            $this->userService->getUser(999);
        } catch (UserServiceException $e) {
            $this->assertEquals(['user_id' => 999], $e->getContext());
        }

        try {
            $this->courseService->createCourse([
                'title' => 'Test Course',
                'description' => 'Test Description',
                'price' => -50.00,
                'instructor_id' => 1
            ]);
        } catch (CourseServiceException $e) {
            $this->assertEquals(['price' => -50.00], $e->getContext());
        }
    }

    public function test_exception_inheritance()
    {
        $this->assertTrue(is_subclass_of(UserServiceException::class, ServiceException::class));
        $this->assertTrue(is_subclass_of(CourseServiceException::class, ServiceException::class));
        $this->assertTrue(is_subclass_of(PaymentServiceException::class, ServiceException::class));
        $this->assertTrue(is_subclass_of(NotificationServiceException::class, ServiceException::class));
    }
} 