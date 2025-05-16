<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;
use Stripe\PaymentIntent;
use Stripe\Subscription as StripeSubscription;
use Stripe\Refund;
use Mockery;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;
    protected $stripeMock;
    protected $user;
    protected $course;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create test course
        $this->course = Course::factory()->create([
            'price' => 99.99,
        ]);

        // Mock Stripe client
        $this->stripeMock = Mockery::mock(StripeClient::class);
        $this->app->instance(StripeClient::class, $this->stripeMock);

        // Create PaymentService instance
        $this->paymentService = new PaymentService(
            new Payment(),
            new Subscription()
        );
    }

    public function test_processes_payment_successfully()
    {
        // Mock successful payment intent
        $paymentIntent = Mockery::mock(PaymentIntent::class);
        $paymentIntent->id = 'pi_test123';
        $paymentIntent->status = 'succeeded';

        $this->stripeMock->shouldReceive('paymentIntents->create')
            ->once()
            ->andReturn($paymentIntent);

        $this->stripeMock->shouldReceive('paymentIntents->confirm')
            ->once()
            ->andReturn($paymentIntent);

        $this->stripeMock->shouldReceive('customers->create')
            ->once()
            ->andReturn((object)['id' => 'cus_test123']);

        $paymentData = [
            'amount' => 99.99,
            'currency' => 'usd',
            'payment_method_id' => 'pm_test123',
            'course_id' => $this->course->id,
        ];

        $payment = $this->paymentService->processPayment($paymentData, $this->user);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(99.99, $payment->amount);
        $this->assertEquals('usd', $payment->currency);
        $this->assertEquals('succeeded', $payment->status);
        $this->assertEquals($this->course->id, $payment->course_id);
    }

    public function test_throws_exception_for_invalid_payment_data()
    {
        $this->expectException(\Exception::class);

        $invalidPaymentData = [
            'amount' => -100, // Invalid amount
            'payment_method_id' => 'pm_test123',
        ];

        $this->paymentService->processPayment($invalidPaymentData, $this->user);
    }

    public function test_creates_subscription_successfully()
    {
        // Mock successful subscription creation
        $stripeSubscription = Mockery::mock(StripeSubscription::class);
        $stripeSubscription->id = 'sub_test123';
        $stripeSubscription->status = 'active';
        $stripeSubscription->current_period_start = time();
        $stripeSubscription->current_period_end = time() + 30 * 24 * 60 * 60;
        $stripeSubscription->latest_invoice = (object)[
            'payment_intent' => (object)['client_secret' => 'pi_test123_secret']
        ];

        $this->stripeMock->shouldReceive('subscriptions->create')
            ->once()
            ->andReturn($stripeSubscription);

        $this->stripeMock->shouldReceive('customers->create')
            ->once()
            ->andReturn((object)['id' => 'cus_test123']);

        $subscriptionData = [
            'price_id' => 'price_test123',
        ];

        $result = $this->paymentService->createSubscription($subscriptionData, $this->user);

        $this->assertInstanceOf(Subscription::class, $result['subscription']);
        $this->assertEquals('active', $result['subscription']->status);
        $this->assertEquals('pi_test123_secret', $result['client_secret']);
    }

    public function test_cancels_subscription_successfully()
    {
        // Create test subscription
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'stripe_subscription_id' => 'sub_test123',
            'status' => 'active',
        ]);

        // Mock successful subscription cancellation
        $stripeSubscription = Mockery::mock(StripeSubscription::class);
        $stripeSubscription->status = 'canceled';

        $this->stripeMock->shouldReceive('subscriptions->update')
            ->once()
            ->andReturn($stripeSubscription);

        $result = $this->paymentService->cancelSubscription($subscription);

        $this->assertTrue($result->cancel_at_period_end);
        $this->assertEquals('canceled', $result->status);
    }

    public function test_processes_refund_successfully()
    {
        // Create test payment
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 99.99,
            'currency' => 'usd',
            'payment_intent_id' => 'pi_test123',
            'status' => 'succeeded',
        ]);

        // Mock successful refund
        $refund = Mockery::mock(Refund::class);
        $refund->id = 're_test123';
        $refund->amount = 9999; // 99.99 in cents
        $refund->currency = 'usd';
        $refund->status = 'succeeded';

        $this->stripeMock->shouldReceive('refunds->create')
            ->once()
            ->andReturn($refund);

        $refundData = [
            'amount' => 99.99,
            'reason' => 'requested_by_customer',
        ];

        $refundRecord = $this->paymentService->processRefund($payment, $refundData);

        $this->assertEquals(99.99, $refundRecord->amount);
        $this->assertEquals('usd', $refundRecord->currency);
        $this->assertEquals('succeeded', $refundRecord->status);
        $this->assertEquals('refunded', $payment->fresh()->status);
    }

    public function test_throws_exception_for_invalid_refund_data()
    {
        $this->expectException(\Exception::class);

        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 99.99,
            'currency' => 'usd',
            'payment_intent_id' => 'pi_test123',
            'status' => 'succeeded',
        ]);

        $invalidRefundData = [
            'amount' => -50, // Invalid amount
            'reason' => 'invalid_reason', // Invalid reason
        ];

        $this->paymentService->processRefund($payment, $invalidRefundData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 