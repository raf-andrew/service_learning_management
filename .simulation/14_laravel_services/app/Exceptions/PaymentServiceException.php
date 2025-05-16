<?php

namespace App\Exceptions;

class PaymentServiceException extends ServiceException
{
    public const PAYMENT_FAILED = 3001;
    public const INVALID_AMOUNT = 3002;
    public const INVALID_PAYMENT_METHOD = 3003;
    public const REFUND_FAILED = 3004;
    public const SUBSCRIPTION_FAILED = 3005;
    public const PAYMENT_NOT_FOUND = 3006;
    public const INSUFFICIENT_FUNDS = 3007;
    public const PAYMENT_GATEWAY_ERROR = 3008;

    public static function paymentFailed(int $userId, int $courseId): self
    {
        return new self(
            "Payment failed for user {$userId} and course {$courseId}",
            self::PAYMENT_FAILED,
            [
                'user_id' => $userId,
                'course_id' => $courseId
            ]
        );
    }

    public static function invalidAmount(float $amount): self
    {
        return new self(
            "Invalid payment amount: {$amount}",
            self::INVALID_AMOUNT,
            ['amount' => $amount]
        );
    }

    public static function invalidPaymentMethod(string $method): self
    {
        return new self(
            "Invalid payment method: {$method}",
            self::INVALID_PAYMENT_METHOD,
            ['method' => $method]
        );
    }

    public static function refundFailed(int $paymentId): self
    {
        return new self(
            "Failed to process refund for payment {$paymentId}",
            self::REFUND_FAILED,
            ['payment_id' => $paymentId]
        );
    }

    public static function subscriptionFailed(int $userId, int $courseId): self
    {
        return new self(
            "Failed to create subscription for user {$userId} and course {$courseId}",
            self::SUBSCRIPTION_FAILED,
            [
                'user_id' => $userId,
                'course_id' => $courseId
            ]
        );
    }

    public static function paymentNotFound(int $paymentId): self
    {
        return new self(
            "Payment with ID {$paymentId} not found",
            self::PAYMENT_NOT_FOUND,
            ['payment_id' => $paymentId]
        );
    }

    public static function insufficientFunds(float $amount): self
    {
        return new self(
            "Insufficient funds for payment amount: {$amount}",
            self::INSUFFICIENT_FUNDS,
            ['amount' => $amount]
        );
    }

    public static function paymentGatewayError(string $message): self
    {
        return new self(
            "Payment gateway error: {$message}",
            self::PAYMENT_GATEWAY_ERROR,
            ['gateway_message' => $message]
        );
    }
} 