<?php

namespace Tests\Feature\Repositories;

use Tests\TestCase;
use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use App\Models\Subscription;
use App\Repositories\PaymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentRepository;
    protected $payment;
    protected $subscription;
    protected $user;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentRepository = new PaymentRepository(new Payment(), new Subscription());
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => 'student'
        ]);

        // Create test course
        $this->course = Course::factory()->create();
        
        // Create test payment
        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'amount' => 100.00,
            'status' => 'completed',
            'type' => 'course_purchase'
        ]);

        // Create test subscription
        $this->subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'type' => 'monthly'
        ]);
    }

    public function test_creates_payment_successfully()
    {
        $paymentData = [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'amount' => 150.00,
            'status' => 'pending',
            'type' => 'course_purchase'
        ];

        $payment = $this->paymentRepository->createPayment($paymentData);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals($paymentData['amount'], $payment->amount);
        $this->assertEquals($paymentData['status'], $payment->status);
        $this->assertEquals($paymentData['type'], $payment->type);
    }

    public function test_finds_payment_by_id()
    {
        $foundPayment = $this->paymentRepository->findPayment($this->payment->id);

        $this->assertInstanceOf(Payment::class, $foundPayment);
        $this->assertEquals($this->payment->id, $foundPayment->id);
        $this->assertEquals($this->payment->amount, $foundPayment->amount);
        $this->assertNotNull($foundPayment->user);
        $this->assertNotNull($foundPayment->course);
    }

    public function test_updates_payment_successfully()
    {
        $updateData = [
            'status' => 'failed',
            'amount' => 90.00
        ];

        $result = $this->paymentRepository->updatePayment($this->payment->id, $updateData);
        $updatedPayment = $this->paymentRepository->findPayment($this->payment->id);

        $this->assertTrue($result);
        $this->assertEquals($updateData['status'], $updatedPayment->status);
        $this->assertEquals($updateData['amount'], $updatedPayment->amount);
    }

    public function test_deletes_payment_successfully()
    {
        $result = $this->paymentRepository->deletePayment($this->payment->id);
        $deletedPayment = $this->paymentRepository->findPayment($this->payment->id);

        $this->assertTrue($result);
        $this->assertNull($deletedPayment);
    }

    public function test_gets_user_payments_with_filters()
    {
        // Create additional test payments
        Payment::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'failed',
            'type' => 'subscription'
        ]);

        // Test filtering by status
        $completedPayments = $this->paymentRepository->getUserPayments($this->user->id, ['status' => 'completed']);
        $this->assertCount(1, $completedPayments);
        $this->assertEquals('completed', $completedPayments->first()->status);

        // Test filtering by type
        $subscriptionPayments = $this->paymentRepository->getUserPayments($this->user->id, ['type' => 'subscription']);
        $this->assertCount(1, $subscriptionPayments);
        $this->assertEquals('subscription', $subscriptionPayments->first()->type);
    }

    public function test_gets_course_payments_with_filters()
    {
        // Create additional test payments
        Payment::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'pending',
            'type' => 'subscription'
        ]);

        // Test filtering by status
        $pendingPayments = $this->paymentRepository->getCoursePayments($this->course->id, ['status' => 'pending']);
        $this->assertCount(1, $pendingPayments);
        $this->assertEquals('pending', $pendingPayments->first()->status);

        // Test filtering by type
        $subscriptionPayments = $this->paymentRepository->getCoursePayments($this->course->id, ['type' => 'subscription']);
        $this->assertCount(1, $subscriptionPayments);
        $this->assertEquals('subscription', $subscriptionPayments->first()->type);
    }

    public function test_creates_subscription_successfully()
    {
        $subscriptionData = [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'type' => 'yearly',
            'start_date' => now(),
            'end_date' => now()->addYear()
        ];

        $subscription = $this->paymentRepository->createSubscription($subscriptionData);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals($subscriptionData['status'], $subscription->status);
        $this->assertEquals($subscriptionData['type'], $subscription->type);
    }

    public function test_finds_subscription_by_id()
    {
        $foundSubscription = $this->paymentRepository->findSubscription($this->subscription->id);

        $this->assertInstanceOf(Subscription::class, $foundSubscription);
        $this->assertEquals($this->subscription->id, $foundSubscription->id);
        $this->assertEquals($this->subscription->status, $foundSubscription->status);
        $this->assertNotNull($foundSubscription->user);
        $this->assertNotNull($foundSubscription->course);
    }

    public function test_updates_subscription_successfully()
    {
        $updateData = [
            'status' => 'expired',
            'type' => 'yearly'
        ];

        $result = $this->paymentRepository->updateSubscription($this->subscription->id, $updateData);
        $updatedSubscription = $this->paymentRepository->findSubscription($this->subscription->id);

        $this->assertTrue($result);
        $this->assertEquals($updateData['status'], $updatedSubscription->status);
        $this->assertEquals($updateData['type'], $updatedSubscription->type);
    }

    public function test_cancels_subscription_successfully()
    {
        $result = $this->paymentRepository->cancelSubscription($this->subscription->id);
        $cancelledSubscription = $this->paymentRepository->findSubscription($this->subscription->id);

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $cancelledSubscription->status);
        $this->assertNotNull($cancelledSubscription->cancelled_at);
    }

    public function test_gets_user_subscriptions_with_filters()
    {
        // Create additional test subscription
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'expired',
            'type' => 'yearly'
        ]);

        // Test filtering by status
        $activeSubscriptions = $this->paymentRepository->getUserSubscriptions($this->user->id, ['status' => 'active']);
        $this->assertCount(1, $activeSubscriptions);
        $this->assertEquals('active', $activeSubscriptions->first()->status);

        // Test filtering by type
        $yearlySubscriptions = $this->paymentRepository->getUserSubscriptions($this->user->id, ['type' => 'yearly']);
        $this->assertCount(1, $yearlySubscriptions);
        $this->assertEquals('yearly', $yearlySubscriptions->first()->type);
    }

    public function test_gets_course_subscriptions_with_filters()
    {
        // Create additional test subscription
        Subscription::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'expired',
            'type' => 'yearly'
        ]);

        // Test filtering by status
        $expiredSubscriptions = $this->paymentRepository->getCourseSubscriptions($this->course->id, ['status' => 'expired']);
        $this->assertCount(1, $expiredSubscriptions);
        $this->assertEquals('expired', $expiredSubscriptions->first()->status);

        // Test filtering by type
        $yearlySubscriptions = $this->paymentRepository->getCourseSubscriptions($this->course->id, ['type' => 'yearly']);
        $this->assertCount(1, $yearlySubscriptions);
        $this->assertEquals('yearly', $yearlySubscriptions->first()->type);
    }

    public function test_gets_active_subscriptions()
    {
        // Create additional test subscriptions
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'expired'
        ]);

        $activeSubscriptions = $this->paymentRepository->getActiveSubscriptions($this->user->id);

        $this->assertCount(2, $activeSubscriptions);
        $this->assertEquals('active', $activeSubscriptions->first()->status);
    }

    public function test_gets_expired_subscriptions()
    {
        // Create additional test subscriptions
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'expired'
        ]);
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        $expiredSubscriptions = $this->paymentRepository->getExpiredSubscriptions($this->user->id);

        $this->assertCount(1, $expiredSubscriptions);
        $this->assertEquals('expired', $expiredSubscriptions->first()->status);
    }

    public function test_gets_payment_stats()
    {
        // Create additional test payments
        Payment::factory()->create([
            'amount' => 200.00,
            'status' => 'completed'
        ]);
        Payment::factory()->create([
            'amount' => 150.00,
            'status' => 'failed'
        ]);

        $stats = $this->paymentRepository->getPaymentStats();

        $this->assertEquals(450.00, $stats['total_amount']);
        $this->assertEquals(3, $stats['total_count']);
        $this->assertEquals(150.00, $stats['average_amount']);
        $this->assertEquals(2, $stats['successful_count']);
        $this->assertEquals(1, $stats['failed_count']);
    }

    public function test_gets_subscription_stats()
    {
        // Create additional test subscriptions
        Subscription::factory()->create(['status' => 'active']);
        Subscription::factory()->create(['status' => 'cancelled']);
        Subscription::factory()->create(['status' => 'expired']);

        $stats = $this->paymentRepository->getSubscriptionStats();

        $this->assertEquals(4, $stats['total_count']);
        $this->assertEquals(2, $stats['active_count']);
        $this->assertEquals(1, $stats['cancelled_count']);
        $this->assertEquals(1, $stats['expired_count']);
    }

    public function test_throws_exception_for_invalid_payment_id()
    {
        $this->expectException(\Exception::class);
        $this->paymentRepository->findPayment(999);
    }

    public function test_throws_exception_for_invalid_subscription_id()
    {
        $this->expectException(\Exception::class);
        $this->paymentRepository->findSubscription(999);
    }
} 