<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SqlInjectionProtectionMiddleware extends BaseMiddleware
{
    /**
     * The URIs that should be excluded from SQL injection protection.
     *
     * @var array
     */
    protected $except = [];

    /**
     * Common SQL injection patterns to detect.
     *
     * @var array
     */
    protected $patterns = [
        // SQL Keywords
        '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|TRUNCATE)\b/i',
        '/\b(OR|AND)\s+\d+\s*=\s*\d+/i',
        '/\b(OR|AND)\s+\d+\s*=\s*\'[^\']*\'/i',
        
        // SQL Comments
        '/--/',
        '/\/\*/',
        '/\*\//',
        
        // SQL Functions
        '/\b(COUNT|SUM|AVG|MAX|MIN)\s*\(/i',
        '/\b(SUBSTRING|CONCAT|CAST|CONVERT)\s*\(/i',
        
        // SQL Operators
        '/\b(IN|BETWEEN|LIKE|IS|NOT)\b/i',
        
        // SQL Injection Techniques
        '/\b(WAITFOR|DELAY|SLEEP)\s*\(/i',
        '/\b(EXEC|EXECUTE|EXECUTE\s+IMMEDIATE)\b/i',
        '/\b(INTO\s+OUTFILE|INTO\s+DUMPFILE)\b/i',
        
        // Common SQL Injection Payloads
        '/\b(OR\s+\d+\s*=\s*\d+\s*--)/i',
        '/\b(OR\s+\d+\s*=\s*\d+\s*#)/i',
        '/\b(OR\s+\d+\s*=\s*\d+\s*\/\*)/i',
        '/\b(OR\s+\d+\s*=\s*\d+\s*;)/i',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldPassThrough($request)) {
            return $next($request);
        }

        if ($this->containsSqlInjection($request)) {
            return $this->handleSqlInjectionAttempt($request);
        }

        return $next($request);
    }

    /**
     * Check if the request contains SQL injection attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function containsSqlInjection(Request $request): bool
    {
        // Check GET parameters
        foreach ($request->query->all() as $key => $value) {
            if ($this->isSqlInjection($value)) {
                return true;
            }
        }

        // Check POST parameters
        foreach ($request->request->all() as $key => $value) {
            if ($this->isSqlInjection($value)) {
                return true;
            }
        }

        // Check JSON input
        if ($request->isJson()) {
            $content = $request->getContent();
            if ($content && $this->isSqlInjection($content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a value contains SQL injection patterns.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isSqlInjection($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle a detected SQL injection attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleSqlInjectionAttempt(Request $request): Response
    {
        $this->logSqlInjectionAttempt($request);

        return response()->json([
            'error' => 'Invalid request',
            'message' => 'The request contains potentially harmful SQL patterns.'
        ], 400);
    }

    /**
     * Log the SQL injection attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function logSqlInjectionAttempt(Request $request): void
    {
        Log::warning('SQL Injection Attempt Detected', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'input' => $this->sanitizeInput($request->all())
        ]);
    }

    /**
     * Sanitize input data for logging.
     *
     * @param  array  $input
     * @return array
     */
    protected function sanitizeInput(array $input): array
    {
        $sensitiveFields = [
            'password',
            'token',
            'secret',
            'api_key',
            'credit_card'
        ];

        foreach ($input as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $input[$key] = '[REDACTED]';
            }
        }

        return $input;
    }

    /**
     * Determine if the request should pass through the middleware.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough(Request $request): bool
    {
        $except = array_merge($this->except, $this->config('sql.except', []));

        foreach ($except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
} 