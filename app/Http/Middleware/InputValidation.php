<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InputValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize input data
        $this->sanitizeInput($request);

        // Validate input data
        $validationResult = $this->validateInput($request);
        
        if (!$validationResult['valid']) {
            Log::warning('Input validation failed', [
                'path' => $request->path(),
                'errors' => $validationResult['errors'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'error' => 'Invalid input data',
                'details' => $validationResult['errors'],
            ], 400);
        }

        return $next($request);
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput(Request $request): void
    {
        // Sanitize query parameters
        $query = $request->query();
        foreach ($query as $key => $value) {
            if (is_string($value)) {
                $query[$key] = $this->sanitizeString($value);
            }
        }
        $request->query->replace($query);

        // Sanitize request body
        $input = $request->input();
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $input[$key] = $this->sanitizeString($value);
            }
        }
        $request->replace($input);
    }

    /**
     * Sanitize a string value
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Remove control characters except newlines and tabs
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        return $value;
    }

    /**
     * Validate input data
     */
    protected function validateInput(Request $request): array
    {
        $errors = [];
        $input = $request->input();

        // Check for SQL injection patterns
        $sqlPatterns = [
            '/\b(union|select|insert|update|delete|drop|create|alter)\b/i',
            '/\b(or|and)\s+\d+\s*=\s*\d+/i',
            '/\b(exec|execute|script)\b/i',
        ];

        foreach ($input as $key => $value) {
            if (is_string($value)) {
                foreach ($sqlPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $errors[] = "Potential SQL injection detected in field: {$key}";
                    }
                }

                // Check for XSS patterns
                $xssPatterns = [
                    '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
                    '/javascript:/i',
                    '/on\w+\s*=/i',
                ];

                foreach ($xssPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        $errors[] = "Potential XSS detected in field: {$key}";
                    }
                }

                // Check for path traversal
                if (preg_match('/\.\./', $value)) {
                    $errors[] = "Potential path traversal detected in field: {$key}";
                }
            }
        }

        // Validate file uploads
        if ($request->hasFile('file')) {
            $fileValidation = $this->validateFileUpload($request->file('file'));
            $errors = array_merge($errors, $fileValidation);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate file upload
     */
    protected function validateFileUpload($file): array
    {
        $errors = [];

        if (!$file->isValid()) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }

        // Check file size
        $maxSize = config('app.max_file_size', 10 * 1024 * 1024); // 10MB
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }

        // Check file extension
        $allowedExtensions = config('app.allowed_file_extensions', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt']);
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'File type not allowed';
        }

        // Check MIME type
        $allowedMimes = config('app.allowed_mime_types', [
            'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'
        ]);
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'File MIME type not allowed';
        }

        return $errors;
    }
} 