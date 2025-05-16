<?php

namespace App\Exceptions;

class UserServiceException extends ServiceException
{
    public const USER_NOT_FOUND = 1001;
    public const INVALID_CREDENTIALS = 1002;
    public const EMAIL_ALREADY_EXISTS = 1003;
    public const INVALID_ROLE = 1004;
    public const UNAUTHORIZED_ACTION = 1005;

    public static function userNotFound(int $userId): self
    {
        return new self(
            "User with ID {$userId} not found",
            self::USER_NOT_FOUND,
            ['user_id' => $userId]
        );
    }

    public static function invalidCredentials(): self
    {
        return new self(
            "Invalid credentials provided",
            self::INVALID_CREDENTIALS
        );
    }

    public static function emailAlreadyExists(string $email): self
    {
        return new self(
            "Email {$email} is already registered",
            self::EMAIL_ALREADY_EXISTS,
            ['email' => $email]
        );
    }

    public static function invalidRole(string $role): self
    {
        return new self(
            "Invalid role: {$role}",
            self::INVALID_ROLE,
            ['role' => $role]
        );
    }

    public static function unauthorizedAction(string $action): self
    {
        return new self(
            "Unauthorized action: {$action}",
            self::UNAUTHORIZED_ACTION,
            ['action' => $action]
        );
    }
} 