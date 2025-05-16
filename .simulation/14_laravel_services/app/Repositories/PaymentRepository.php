<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository
{
    protected $payment;
    protected $subscription;

    public function __construct(Payment $payment, Subscription $subscription)
    {
        $this->payment = $payment;
        $this->subscription = $subscription;
    }

    public function createPayment(array $data): Payment
    {
        try {
            return $this->payment->create($data);
        } catch (\Exception $e) {
            Log::error('Failed to create payment: ' . $e->getMessage());
            throw new \Exception('Failed to create payment: ' . $e->getMessage());
        }
    }

    public function findPayment(int $id): ?Payment
    {
        try {
            return $this->payment->with(['user', 'course'])->find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find payment: ' . $e->getMessage());
            throw new \Exception('Failed to find payment: ' . $e->getMessage());
        }
    }

    public function updatePayment(int $id, array $data): bool
    {
        try {
            $payment = $this->findPayment($id);
            if (!$payment) {
                return false;
            }

            return $payment->update($data);
        } catch (\Exception $e) {
            Log::error('Failed to update payment: ' . $e->getMessage());
            throw new \Exception('Failed to update payment: ' . $e->getMessage());
        }
    }

    public function deletePayment(int $id): bool
    {
        try {
            $payment = $this->findPayment($id);
            if (!$payment) {
                return false;
            }

            return $payment->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete payment: ' . $e->getMessage());
            throw new \Exception('Failed to delete payment: ' . $e->getMessage());
        }
    }

    public function getUserPayments(int $userId, array $filters = []): Collection
    {
        try {
            $query = $this->payment->with(['course'])
                ->where('user_id', $userId);

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error('Failed to get user payments: ' . $e->getMessage());
            throw new \Exception('Failed to get user payments: ' . $e->getMessage());
        }
    }

    public function getCoursePayments(int $courseId, array $filters = []): Collection
    {
        try {
            $query = $this->payment->with(['user'])
                ->where('course_id', $courseId);

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error('Failed to get course payments: ' . $e->getMessage());
            throw new \Exception('Failed to get course payments: ' . $e->getMessage());
        }
    }

    public function createSubscription(array $data): Subscription
    {
        try {
            return $this->subscription->create($data);
        } catch (\Exception $e) {
            Log::error('Failed to create subscription: ' . $e->getMessage());
            throw new \Exception('Failed to create subscription: ' . $e->getMessage());
        }
    }

    public function findSubscription(int $id): ?Subscription
    {
        try {
            return $this->subscription->with(['user', 'course'])->find($id);
        } catch (\Exception $e) {
            Log::error('Failed to find subscription: ' . $e->getMessage());
            throw new \Exception('Failed to find subscription: ' . $e->getMessage());
        }
    }

    public function updateSubscription(int $id, array $data): bool
    {
        try {
            $subscription = $this->findSubscription($id);
            if (!$subscription) {
                return false;
            }

            return $subscription->update($data);
        } catch (\Exception $e) {
            Log::error('Failed to update subscription: ' . $e->getMessage());
            throw new \Exception('Failed to update subscription: ' . $e->getMessage());
        }
    }

    public function cancelSubscription(int $id): bool
    {
        try {
            $subscription = $this->findSubscription($id);
            if (!$subscription) {
                return false;
            }

            return $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription: ' . $e->getMessage());
            throw new \Exception('Failed to cancel subscription: ' . $e->getMessage());
        }
    }

    public function getUserSubscriptions(int $userId, array $filters = []): Collection
    {
        try {
            $query = $this->subscription->with(['course'])
                ->where('user_id', $userId);

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error('Failed to get user subscriptions: ' . $e->getMessage());
            throw new \Exception('Failed to get user subscriptions: ' . $e->getMessage());
        }
    }

    public function getCourseSubscriptions(int $courseId, array $filters = []): Collection
    {
        try {
            $query = $this->subscription->with(['user'])
                ->where('course_id', $courseId);

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            return $query->get();
        } catch (\Exception $e) {
            Log::error('Failed to get course subscriptions: ' . $e->getMessage());
            throw new \Exception('Failed to get course subscriptions: ' . $e->getMessage());
        }
    }

    public function getActiveSubscriptions(int $userId): Collection
    {
        try {
            return $this->subscription->with(['course'])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get active subscriptions: ' . $e->getMessage());
            throw new \Exception('Failed to get active subscriptions: ' . $e->getMessage());
        }
    }

    public function getExpiredSubscriptions(int $userId): Collection
    {
        try {
            return $this->subscription->with(['course'])
                ->where('user_id', $userId)
                ->where('status', 'expired')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get expired subscriptions: ' . $e->getMessage());
            throw new \Exception('Failed to get expired subscriptions: ' . $e->getMessage());
        }
    }

    public function getPaymentStats(array $filters = []): array
    {
        try {
            $query = $this->payment->query();

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            return [
                'total_amount' => $query->sum('amount'),
                'total_count' => $query->count(),
                'average_amount' => $query->avg('amount'),
                'successful_count' => $query->where('status', 'completed')->count(),
                'failed_count' => $query->where('status', 'failed')->count()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get payment stats: ' . $e->getMessage());
            throw new \Exception('Failed to get payment stats: ' . $e->getMessage());
        }
    }

    public function getSubscriptionStats(array $filters = []): array
    {
        try {
            $query = $this->subscription->query();

            if (isset($filters['start_date'])) {
                $query->where('created_at', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->where('created_at', '<=', $filters['end_date']);
            }

            return [
                'total_count' => $query->count(),
                'active_count' => $query->where('status', 'active')->count(),
                'cancelled_count' => $query->where('status', 'cancelled')->count(),
                'expired_count' => $query->where('status', 'expired')->count()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get subscription stats: ' . $e->getMessage());
            throw new \Exception('Failed to get subscription stats: ' . $e->getMessage());
        }
    }
} 