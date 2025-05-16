<?php

namespace App\Exceptions;

class NotificationServiceException extends ServiceException
{
    public const INVALID_RECIPIENT = 4001;
    public const INVALID_TEMPLATE = 4002;
    public const DELIVERY_FAILED = 4003;
    public const INVALID_NOTIFICATION_TYPE = 4004;
    public const QUEUE_ERROR = 4005;
    public const WEBSOCKET_ERROR = 4006;
    public const PUSH_NOTIFICATION_FAILED = 4007;

    public static function invalidRecipient(int $userId): self
    {
        return new self(
            "Invalid notification recipient: {$userId}",
            self::INVALID_RECIPIENT,
            ['user_id' => $userId]
        );
    }

    public static function invalidTemplate(string $template): self
    {
        return new self(
            "Invalid notification template: {$template}",
            self::INVALID_TEMPLATE,
            ['template' => $template]
        );
    }

    public static function deliveryFailed(string $type, int $userId): self
    {
        return new self(
            "Failed to deliver {$type} notification to user {$userId}",
            self::DELIVERY_FAILED,
            [
                'type' => $type,
                'user_id' => $userId
            ]
        );
    }

    public static function invalidNotificationType(string $type): self
    {
        return new self(
            "Invalid notification type: {$type}",
            self::INVALID_NOTIFICATION_TYPE,
            ['type' => $type]
        );
    }

    public static function queueError(string $message): self
    {
        return new self(
            "Notification queue error: {$message}",
            self::QUEUE_ERROR,
            ['queue_message' => $message]
        );
    }

    public static function websocketError(string $message): self
    {
        return new self(
            "WebSocket error: {$message}",
            self::WEBSOCKET_ERROR,
            ['websocket_message' => $message]
        );
    }

    public static function pushNotificationFailed(string $deviceToken): self
    {
        return new self(
            "Failed to send push notification to device: {$deviceToken}",
            self::PUSH_NOTIFICATION_FAILED,
            ['device_token' => $deviceToken]
        );
    }
} 