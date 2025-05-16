<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class LoggingService
{
    protected $logLevel;
    protected $sensitiveHeaders;
    protected $sensitiveParams;

    public function __construct()
    {
        $this->logLevel = config('logging.level', 'info');
        $this->sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        $this->sensitiveParams = ['password', 'token', 'secret'];
    }

    public function logRequest(Request $request): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query' => $this->sanitizeParams($request->query()),
            'body' => $this->sanitizeParams($request->all()),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        Log::channel('api')->info('API Request', $data);
    }

    public function logResponse(Request $request, Response $response): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->status(),
            'headers' => $this->sanitizeHeaders($response->headers->all()),
            'response_time' => microtime(true) - LARAVEL_START,
        ];

        Log::channel('api')->info('API Response', $data);
    }

    public function logError(Request $request, Throwable $error): void
    {
        $data = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'error' => [
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTraceAsString(),
            ],
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        Log::channel('api')->error('API Error', $data);
    }

    public function logAccess(Request $request, Response $response): void
    {
        $data = [
            'timestamp' => now()->toIso8601String(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->status(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'response_time' => microtime(true) - LARAVEL_START,
        ];

        Log::channel('access')->info('Access Log', $data);
    }

    protected function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, $this->sensitiveHeaders)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    protected function sanitizeParams(array $params): array
    {
        $sanitized = [];
        foreach ($params as $key => $value) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, $this->sensitiveParams)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    public function setLogLevel(string $level): void
    {
        $this->logLevel = $level;
    }

    public function addSensitiveHeader(string $header): void
    {
        $this->sensitiveHeaders[] = strtolower($header);
    }

    public function addSensitiveParam(string $param): void
    {
        $this->sensitiveParams[] = strtolower($param);
    }
} 