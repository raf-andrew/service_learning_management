<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Repositories\UserRepository;
use App\Repositories\CourseRepository;
use App\Repositories\PaymentRepository;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class RepositoryPerformanceTest extends TestCase
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

    public function test_user_repository_performance()
    {
        $startTime = microtime(true);
        
        // Test user creation performance
        $user = $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $creationTime = microtime(true) - $startTime;
        $this->assertLessThan(0.3, $creationTime, 'User creation took longer than expected');

        // Test user retrieval performance
        $startTime = microtime(true);
        $retrievedUser = $this->userRepository->find($user->id);
        $retrievalTime = microtime(true) - $startTime;
        $this->assertLessThan(0.1, $retrievalTime, 'User retrieval took longer than expected');

        // Test user update performance
        $startTime = microtime(true);
        $this->userRepository->update($user->id, ['name' => 'Updated Name']);
        $updateTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $updateTime, 'User update took longer than expected');

        // Test user deletion performance
        $startTime = microtime(true);
        $this->userRepository->delete($user->id);
        $deleteTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $deleteTime, 'User deletion took longer than expected');
    }

    public function test_course_repository_performance()
    {
        // Create test user
        $user = $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $startTime = microtime(true);
        
        // Test course creation performance
        $course = $this->courseRepository->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => $user->id
        ]);

        $creationTime = microtime(true) - $startTime;
        $this->assertLessThan(0.3, $creationTime, 'Course creation took longer than expected');

        // Test course retrieval with relationships
        $startTime = microtime(true);
        $courseWithRelations = $this->courseRepository->findWithRelations($course->id);
        $retrievalTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $retrievalTime, 'Course retrieval with relationships took longer than expected');

        // Test course update performance
        $startTime = microtime(true);
        $this->courseRepository->update($course->id, ['title' => 'Updated Course']);
        $updateTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $updateTime, 'Course update took longer than expected');
    }

    public function test_payment_repository_performance()
    {
        // Create test user and course
        $user = $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $course = $this->courseRepository->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => $user->id
        ]);

        $startTime = microtime(true);
        
        // Test payment creation performance
        $payment = $this->paymentRepository->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'status' => 'completed',
            'payment_method' => 'card'
        ]);

        $creationTime = microtime(true) - $startTime;
        $this->assertLessThan(0.3, $creationTime, 'Payment creation took longer than expected');

        // Test payment retrieval with relationships
        $startTime = microtime(true);
        $paymentWithRelations = $this->paymentRepository->findWithRelations($payment->id);
        $retrievalTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $retrievalTime, 'Payment retrieval with relationships took longer than expected');
    }

    public function test_bulk_operations_performance()
    {
        $startTime = microtime(true);
        
        // Test bulk user creation
        $users = [];
        for ($i = 0; $i < 50; $i++) {
            $users[] = [
                'name' => "Test User $i",
                'email' => "test$i@example.com",
                'password' => 'password123',
                'role' => 'student'
            ];
        }

        $createdUsers = $this->userRepository->createMany($users);
        $bulkCreationTime = microtime(true) - $startTime;
        $this->assertLessThan(2.0, $bulkCreationTime, 'Bulk user creation took longer than expected');

        // Test bulk course creation
        $startTime = microtime(true);
        $courses = [];
        foreach ($createdUsers as $user) {
            $courses[] = [
                'title' => "Test Course for User {$user->id}",
                'description' => "Test Description for User {$user->id}",
                'price' => 99.99,
                'instructor_id' => $user->id
            ];
        }

        $createdCourses = $this->courseRepository->createMany($courses);
        $bulkCourseCreationTime = microtime(true) - $startTime;
        $this->assertLessThan(2.0, $bulkCourseCreationTime, 'Bulk course creation took longer than expected');
    }

    public function test_query_optimization()
    {
        // Create test data
        $user = $this->userRepository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'student'
        ]);

        $course = $this->courseRepository->create([
            'title' => 'Test Course',
            'description' => 'Test Description',
            'price' => 99.99,
            'instructor_id' => $user->id
        ]);

        // Test query count
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        $this->courseRepository->findWithRelations($course->id);
        $queries = DB::getQueryLog();
        
        $queryTime = microtime(true) - $startTime;
        $this->assertLessThan(0.2, $queryTime, 'Query execution took longer than expected');
        $this->assertLessThan(3, count($queries), 'Too many queries executed for a single operation');
    }

    public function test_index_performance()
    {
        // Create test data
        $users = [];
        for ($i = 0; $i < 100; $i++) {
            $users[] = $this->userRepository->create([
                'name' => "Test User $i",
                'email' => "test$i@example.com",
                'password' => 'password123',
                'role' => 'student'
            ]);
        }

        $startTime = microtime(true);
        
        // Test paginated retrieval
        $paginatedUsers = $this->userRepository->paginate(10);
        $paginationTime = microtime(true) - $startTime;
        $this->assertLessThan(0.3, $paginationTime, 'Paginated retrieval took longer than expected');

        // Test filtered retrieval
        $startTime = microtime(true);
        $filteredUsers = $this->userRepository->findBy(['role' => 'student']);
        $filterTime = microtime(true) - $startTime;
        $this->assertLessThan(0.3, $filterTime, 'Filtered retrieval took longer than expected');
    }
} 