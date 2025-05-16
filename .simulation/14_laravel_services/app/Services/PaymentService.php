<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Models\Course;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Subscription as StripeSubscription;
use Stripe\Refund;

class PaymentService
{
    protected $payment;
    protected $subscription;
    protected $stripe;

    public function __construct(Payment $payment, Subscription $subscription)
    {
        $this->payment = $payment;
        $this->subscription = $subscription;
        $this->stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
    }

    public function processPayment(array $data, User $user)
    {
        try {
            // Validate payment data
            $this->validatePaymentData($data);

            // Create payment intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $data['amount'] * 100, // Convert to cents
                'currency' => $data['currency'] ?? 'usd',
                'payment_method' => $data['payment_method_id'],
                'customer' => $this->getOrCreateStripeCustomer($user),
                'metadata' => [
                    'user_id' => $user->id,
                    'course_id' => $data['course_id'] ?? null,
                    'subscription_id' => $data['subscription_id'] ?? null,
                ],
            ]);

            // Confirm payment intent
            $paymentIntent = $this->stripe->paymentIntents->confirm(
                $paymentIntent->id,
                ['payment_method' => $data['payment_method_id']]
            );

            // Create payment record
            $payment = $this->payment->create([
                'user_id' => $user->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'usd',
                'payment_method' => 'stripe',
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'course_id' => $data['course_id'] ?? null,
                'subscription_id' => $data['subscription_id'] ?? null,
            ]);

            // Handle successful payment
            if ($paymentIntent->status === 'succeeded') {
                $this->handleSuccessfulPayment($payment);
            }

            return $payment;
        } catch (\Exception $e) {
            Log::error('Payment processing failed: ' . $e->getMessage());
            throw new \Exception('Payment processing failed: ' . $e->getMessage());
        }
    }

    public function createSubscription(array $data, User $user)
    {
        try {
            // Validate subscription data
            $this->validateSubscriptionData($data);

            // Create Stripe subscription
            $stripeSubscription = $this->stripe->subscriptions->create([
                'customer' => $this->getOrCreateStripeCustomer($user),
                'items' => [['price' => $data['price_id']]],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            // Create subscription record
            $subscription = $this->subscription->create([
                'user_id' => $user->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_price_id' => $data['price_id'],
                'status' => $stripeSubscription->status,
                'current_period_start' => $stripeSubscription->current_period_start,
                'current_period_end' => $stripeSubscription->current_period_end,
                'cancel_at_period_end' => false,
            ]);

            return [
                'subscription' => $subscription,
                'client_secret' => $stripeSubscription->latest_invoice->payment_intent->client_secret,
            ];
        } catch (\Exception $e) {
            Log::error('Subscription creation failed: ' . $e->getMessage());
            throw new \Exception('Subscription creation failed: ' . $e->getMessage());
        }
    }

    public function cancelSubscription(Subscription $subscription)
    {
        try {
            // Cancel Stripe subscription
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['cancel_at_period_end' => true]
            );

            // Update subscription record
            $subscription->update([
                'status' => $stripeSubscription->status,
                'cancel_at_period_end' => true,
            ]);

            return $subscription;
        } catch (\Exception $e) {
            Log::error('Subscription cancellation failed: ' . $e->getMessage());
            throw new \Exception('Subscription cancellation failed: ' . $e->getMessage());
        }
    }

    public function processRefund(Payment $payment, array $data = [])
    {
        try {
            // Validate refund data
            $this->validateRefundData($data);

            // Process Stripe refund
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $payment->payment_intent_id,
                'amount' => isset($data['amount']) ? $data['amount'] * 100 : null,
                'reason' => $data['reason'] ?? null,
            ]);

            // Create refund record
            $refundRecord = $payment->refunds()->create([
                'amount' => $refund->amount / 100,
                'currency' => $refund->currency,
                'stripe_refund_id' => $refund->id,
                'reason' => $data['reason'] ?? null,
                'status' => $refund->status,
            ]);

            // Update payment status if fully refunded
            if ($refund->amount === $payment->amount * 100) {
                $payment->update(['status' => 'refunded']);
            }

            return $refundRecord;
        } catch (\Exception $e) {
            Log::error('Refund processing failed: ' . $e->getMessage());
            throw new \Exception('Refund processing failed: ' . $e->getMessage());
        }
    }

    protected function getOrCreateStripeCustomer(User $user)
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    protected function handleSuccessfulPayment(Payment $payment)
    {
        // Handle course enrollment if applicable
        if ($payment->course_id) {
            $course = Course::find($payment->course_id);
            if ($course) {
                app(CourseService::class)->enrollUser($course, $payment->user);
            }
        }

        // Handle subscription activation if applicable
        if ($payment->subscription_id) {
            $subscription = Subscription::find($payment->subscription_id);
            if ($subscription) {
                $subscription->update(['status' => 'active']);
            }
        }
    }

    protected function validatePaymentData(array $data)
    {
        $validator = \Validator::make($data, [
            'amount' => 'required|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'payment_method_id' => 'required|string',
            'course_id' => 'sometimes|exists:courses,id',
            'subscription_id' => 'sometimes|exists:subscriptions,id',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    protected function validateSubscriptionData(array $data)
    {
        $validator = \Validator::make($data, [
            'price_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }

    protected function validateRefundData(array $data)
    {
        $validator = \Validator::make($data, [
            'amount' => 'sometimes|numeric|min:0',
            'reason' => 'sometimes|in:requested_by_customer,duplicate,fraudulent',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }
    }
} 