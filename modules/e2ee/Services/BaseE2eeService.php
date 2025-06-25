<?php

namespace App\Modules\E2ee\Services;

use App\Modules\E2ee\Traits\E2eeConfigTrait;
use App\Modules\Shared\Services\Core\AuditService;
use Illuminate\Support\Facades\Log;
use App\Modules\E2ee\Exceptions\E2eeException;

abstract class BaseE2eeService
{
    use E2eeConfigTrait;

    /**
     * @var AuditService
     */
    protected AuditService $auditService;

    /**
     * @var bool
     */
    protected bool $auditEnabled;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->auditEnabled = $this->isAuditEnabled();
    }

    /**
     * Log operation for audit purposes
     */
    protected function logOperation(string $action, array $context = []): void
    {
        if ($this->auditEnabled) {
            $this->auditService->log('e2ee', $action, $context);
        }

        Log::info("E2EE: {$action}", $context);
    }

    /**
     * Log error for debugging
     */
    protected function logError(string $operation, string $error, array $context = []): void
    {
        $logContext = array_merge($context, [
            'operation' => $operation,
            'error' => $error,
            'timestamp' => now()->toISOString(),
        ]);

        Log::error("E2EE {$operation} error", $logContext);

        if ($this->auditEnabled) {
            $this->auditService->log('e2ee_error', $operation, $logContext);
        }
    }

    /**
     * Execute operation with error handling
     */
    protected function executeWithErrorHandling(callable $operation, string $operationName, array $context = []): mixed
    {
        try {
            $this->logOperation("{$operationName}_start", $context);
            
            $result = $operation();
            
            $this->logOperation("{$operationName}_success", array_merge($context, [
                'result_type' => gettype($result)
            ]));
            
            return $result;
        } catch (\Exception $e) {
            $this->logError($operationName, $e->getMessage(), $context);
            throw $this->createE2eeException($e, $operationName);
        }
    }

    /**
     * Create E2EE exception with context
     */
    protected function createE2eeException(\Exception $e, string $operationName): E2eeException
    {
        $errorCode = strtoupper($operationName . '_error');
        $message = "E2EE {$operationName} failed: " . $e->getMessage();
        
        return new E2eeException($message, $errorCode, [
            'operation' => $operationName,
            'original_exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }

    /**
     * Validate user ID
     */
    protected function validateUserId(int $userId): void
    {
        if ($userId <= 0) {
            throw new E2eeException('Invalid user ID', 'INVALID_USER_ID', ['user_id' => $userId]);
        }
    }

    /**
     * Validate input data
     */
    protected function validateInputData(string $data, string $operationName): void
    {
        if (empty($data)) {
            throw new E2eeException('Data cannot be empty', 'EMPTY_DATA', ['operation' => $operationName]);
        }
    }

    /**
     * Get service statistics
     */
    abstract public function getStatistics(): array;

    /**
     * Validate service parameters
     */
    abstract public function validateParameters(): bool;
} 