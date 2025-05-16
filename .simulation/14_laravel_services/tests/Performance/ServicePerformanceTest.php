<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Services\UserService;
use App\Services\CourseService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class ServicePerformanceTest extends TestCase
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

    public function test_user_service_performance()
    {
        $startTime = microtime(true);
        
        // Test user creation performance
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $creationTime = microtime(true) - $startTime;
        $this->assertLessThan(0.5, $creationTime, 'User creation took longer than expected');

        // Test user retrieval performance
        $startTime = microtime(true);
        $retrievedUser = $this->userService->find($user->id);
        $retrievalTime = microtime(true) - $startTime;
        $this->assertLessThan(0.1, $retrievalTime, 'User retrieval took longer than expected');

        // Test user update performance
        $startTime = microtime(true);
        $this->userService->update($user->id, ['name' => 'Updated Name']);
        $updateTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $updateTime, 'User update took longer than expected');
    }

    public function test_course_service_performance()
    {
        // Create test user
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $startTime = microtime(true);
        
        // Test course creation performance
        $course = $this->courseService->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => 1
        ]);

        $creationTime = microtime(true) - $startTime;
        $this->assertLessThan(0.5, $creationTime, 'Course creation took longer than expected');

        // Test enrollment performance
        $startTime = microtime(true);
        $this->courseService->enrollStudent($course->id, $user->id);
        $enrollmentTime = microtime(true) - $startTime;
        $this->assertLessThan(0.3, $enrollmentTime, 'Course enrollment took longer than expected');

        // Test course retrieval with relationships
        $startTime = microtime(true);
        $courseWithStudents = $this->courseService->find($course->id);
        $retrievalTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $retrievalTime, 'Course retrieval with relationships took longer than expected');
    }

    public function test_payment_service_performance()
    {
        // Create test user and course
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $course = $this->courseService->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => 1
        ]);

        $startTime = microtime(true);
        
        // Test payment processing performance
        $payment = $this->paymentService->processPayment([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'payment_method' => 'card',
            'payment_intent_id' => 'test_intent_123'
        ]);

        $paymentTime = microtime(true) - $startTime;
        $this->assertLessThan(1.0, $paymentTime, 'Payment processing took longer than expected');

        // Test subscription creation performance
        $startTime = microtime(true);
        $subscription = $this->paymentService->createSubscription([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'type' => 'monthly',
            'status' => 'active'
        ]);

        $subscriptionTime = microtime(true) - $startTime;
        $this->assertLessThan(0.5, $subscriptionTime, 'Subscription creation took longer than expected');
    }

    public function test_notification_service_performance()
    {
        // Create test user
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $startTime = microtime(true);
        
        // Test email notification performance
        $this->notificationService->sendEmailNotification(
            $user->id,
            'test_notification',
            ['message' => 'Test message']
        );

        $emailTime = microtime(true) - $startTime;
        $this->assertLessThan(0.5, $emailTime, 'Email notification took longer than expected');

        // Test in-app notification performance
        $startTime = microtime(true);
        $this->notificationService->sendInAppNotification(
            $user->id,
            'test_notification',
            ['message' => 'Test message']
        );

        $inAppTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $inAppTime, 'In-app notification took longer than expected');
    }

    public function test_concurrent_operations_performance()
    {
        $startTime = microtime(true);
        
        // Create multiple users concurrently
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $users[] = $this->userService->create([
                'name' => "Test User $i",
                'email' => "test$i@example.com",
                'password' => 'password123',
                'role' => 'student'
            ]);
        }

        $userCreationTime = microtime(true) - $startTime;
        $this->assertLessThan(2.0, $userCreationTime, 'Concurrent user creation took longer than expected');

        // Create multiple courses concurrently
        $startTime = microtime(true);
        $courses = [];
        for ($i = 0; $i < 10; $i++) {
            $courses[] = $this->courseService->create([
                'title' => "Test Course $i",
                'description' => "Test Description $i",
                'price' => 99.99,
                'instructor_id' => 1
            ]);
        }

        $courseCreationTime = microtime(true) - $startTime;
        $this->assertLessThan(2.0, $courseCreationTime, 'Concurrent course creation took longer than expected');
    }

    public function test_database_query_performance()
    {
        // Create test data
        $user = $this->userService->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $course = $this->courseService->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => 1
        ]);

        // Test query performance
        $startTime = microtime(true);
        
        DB::enableQueryLog();
        $this->courseService->enrollStudent($course->id, $user->id);
        $queries = DB::getQueryLog();
        
        $queryTime = microtime(true) - $startTime;
        $this->assertLessThan(0.3, $queryTime, 'Database queries took longer than expected');
        $this->assertLessThan(5, count($queries), 'Too many queries executed');
    }
} 