<?php

namespace App\Exceptions;

use Exception;

/**
 * Credential Exception
 * 
 * Custom exception for credential-related errors with structured error codes
 * and user-friendly messages.
 */
class CredentialException extends Exception
{
    /**
     * Error codes for different credential issues.
     */
    public const ERROR_CODES = [
        'INVALID_TOKEN' => 'CRED_001',
        'EXPIRED_TOKEN' => 'CRED_002',
        'DUPLICATE_USERNAME' => 'CRED_003',
        'INSUFFICIENT_PERMISSIONS' => 'CRED_004',
        'USER_NOT_FOUND' => 'CRED_005',
        'CREDENTIAL_NOT_FOUND' => 'CRED_006',
        'VALIDATION_FAILED' => 'CRED_007',
        'RATE_LIMIT_EXCEEDED' => 'CRED_008',
        'GITHUB_API_ERROR' => 'CRED_009',
        'ENCRYPTION_ERROR' => 'CRED_010',
    ];

    /**
     * The error code for this exception.
     *
     * @var string
     */
    protected string $errorCode;

    /**
     * Additional context data.
     *
     * @var array<string, mixed>
     */
    protected array $context;

    /**
     * Create a new credential exception instance.
     *
     * @param string $message
     * @param string $errorCode
     * @param array<string, mixed> $context
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = '',
        string $errorCode = 'CRED_000',
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Get the error code.
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the context data.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create an exception for invalid token.
     *
     * @param string $token
     * @return static
     */
    public static function invalidToken(string $token): static
    {
        return new static(
            'The provided GitHub token is invalid or malformed.',
            self::ERROR_CODES['INVALID_TOKEN'],
            ['token_length' => strlen($token)]
        );
    }

    /**
     * Create an exception for expired token.
     *
     * @param string $expiresAt
     * @return static
     */
    public static function expiredToken(string $expiresAt): static
    {
        return new static(
            'The GitHub token has expired.',
            self::ERROR_CODES['EXPIRED_TOKEN'],
            ['expires_at' => $expiresAt]
        );
    }

    /**
     * Create an exception for duplicate username.
     *
     * @param string $username
     * @return static
     */
    public static function duplicateUsername(string $username): static
    {
        return new static(
            'A credential with this GitHub username already exists.',
            self::ERROR_CODES['DUPLICATE_USERNAME'],
            ['username' => $username]
        );
    }

    /**
     * Create an exception for insufficient permissions.
     *
     * @param array<string> $required
     * @param array<string> $provided
     * @return static
     */
    public static function insufficientPermissions(array $required, array $provided): static
    {
        return new static(
            'The credential does not have sufficient permissions for this operation.',
            self::ERROR_CODES['INSUFFICIENT_PERMISSIONS'],
            [
                'required_permissions' => $required,
                'provided_permissions' => $provided,
            ]
        );
    }

    /**
     * Create an exception for user not found.
     *
     * @param int $userId
     * @return static
     */
    public static function userNotFound(int $userId): static
    {
        return new static(
            'The specified user was not found.',
            self::ERROR_CODES['USER_NOT_FOUND'],
            ['user_id' => $userId]
        );
    }

    /**
     * Create an exception for credential not found.
     *
     * @param int $credentialId
     * @return static
     */
    public static function credentialNotFound(int $credentialId): static
    {
        return new static(
            'The specified credential was not found.',
            self::ERROR_CODES['CREDENTIAL_NOT_FOUND'],
            ['credential_id' => $credentialId]
        );
    }

    /**
     * Create an exception for validation failure.
     *
     * @param array<string, mixed> $errors
     * @return static
     */
    public static function validationFailed(array $errors): static
    {
        return new static(
            'The provided data failed validation.',
            self::ERROR_CODES['VALIDATION_FAILED'],
            ['validation_errors' => $errors]
        );
    }

    /**
     * Create an exception for rate limit exceeded.
     *
     * @param int $retryAfter
     * @return static
     */
    public static function rateLimitExceeded(int $retryAfter): static
    {
        return new static(
            'Rate limit exceeded. Please try again later.',
            self::ERROR_CODES['RATE_LIMIT_EXCEEDED'],
            ['retry_after' => $retryAfter]
        );
    }

    /**
     * Create an exception for GitHub API error.
     *
     * @param string $apiError
     * @param int $statusCode
     * @return static
     */
    public static function githubApiError(string $apiError, int $statusCode): static
    {
        return new static(
            'GitHub API error occurred.',
            self::ERROR_CODES['GITHUB_API_ERROR'],
            [
                'api_error' => $apiError,
                'status_code' => $statusCode,
            ]
        );
    }

    /**
     * Create an exception for encryption error.
     *
     * @param string $operation
     * @return static
     */
    public static function encryptionError(string $operation): static
    {
        return new static(
            'An error occurred during encryption/decryption.',
            self::ERROR_CODES['ENCRYPTION_ERROR'],
            ['operation' => $operation]
        );
    }

    /**
     * Convert the exception to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
} 