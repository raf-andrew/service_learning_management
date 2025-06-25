<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Has Validation Trait
 * 
 * Provides consistent validation functionality across classes.
 * This trait includes methods for data validation with custom rules.
 */
trait HasValidation
{
    /**
     * Validate data with rules
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateData(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }

    /**
     * Validate data without throwing exception
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     */
    protected function validateDataSilently(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);
        
        if ($validator->fails()) {
            return ['valid' => false, 'errors' => $validator->errors()];
        }
        
        return ['valid' => true, 'data' => $validator->validated()];
    }

    /**
     * Sanitize input data
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize string value
     *
     * @param string $value
     * @return string
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // Convert special characters
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        return $value;
    }

    /**
     * Validate email address
     *
     * @param string $email
     * @return bool
     */
    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return bool
     */
    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate IP address
     *
     * @param string $ip
     * @return bool
     */
    protected function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Validate date format
     *
     * @param string $date
     * @param string $format
     * @return bool
     */
    protected function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate JSON string
     *
     * @param string $json
     * @return bool
     */
    protected function isValidJson(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Validate GitHub token format
     *
     * @param string $token
     * @return bool
     */
    protected function isValidGitHubToken(string $token): bool
    {
        // GitHub tokens are typically 40 characters long and contain alphanumeric characters
        return preg_match('/^[a-zA-Z0-9]{40}$/', $token) === 1;
    }

    /**
     * Validate username format
     *
     * @param string $username
     * @return bool
     */
    protected function isValidUsername(string $username): bool
    {
        // Username should be 3-30 characters, alphanumeric, hyphens, and underscores only
        return preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username) === 1;
    }

    /**
     * Validate password strength
     *
     * @param string $password
     * @return array<string, mixed>
     */
    protected function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => $this->calculatePasswordScore($password)
        ];
    }

    /**
     * Calculate password strength score
     *
     * @param string $password
     * @return int
     */
    protected function calculatePasswordScore(string $password): int
    {
        $score = 0;
        
        // Length bonus
        $score += min(strlen($password) * 2, 20);
        
        // Character variety bonus
        if (preg_match('/[a-z]/', $password)) $score += 5;
        if (preg_match('/[A-Z]/', $password)) $score += 5;
        if (preg_match('/[0-9]/', $password)) $score += 5;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 5;
        
        // Deduct for common patterns
        if (preg_match('/(.)\1{2,}/', $password)) $score -= 10;
        if (preg_match('/123|abc|qwe/i', $password)) $score -= 15;
        
        return max(0, min(100, $score));
    }

    /**
     * Get common validation rules
     *
     * @return array<string, array<string, string>>
     */
    protected function getCommonValidationRules(): array
    {
        return [
            'email' => [
                'email' => 'required|email|max:255',
                'email_optional' => 'nullable|email|max:255',
            ],
            'username' => [
                'username' => 'required|string|min:3|max:30|regex:/^[a-zA-Z0-9_-]+$/',
                'username_optional' => 'nullable|string|min:3|max:30|regex:/^[a-zA-Z0-9_-]+$/',
            ],
            'password' => [
                'password' => 'required|string|min:8|max:255',
                'password_optional' => 'nullable|string|min:8|max:255',
            ],
            'name' => [
                'name' => 'required|string|min:2|max:255',
                'name_optional' => 'nullable|string|min:2|max:255',
            ],
            'url' => [
                'url' => 'required|url|max:2048',
                'url_optional' => 'nullable|url|max:2048',
            ],
            'github_token' => [
                'github_token' => 'required|string|regex:/^[a-zA-Z0-9]{40}$/',
                'github_token_optional' => 'nullable|string|regex:/^[a-zA-Z0-9]{40}$/',
            ],
        ];
    }

    /**
     * Get common validation messages
     *
     * @return array<string, string>
     */
    protected function getCommonValidationMessages(): array
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'username.regex' => 'Username can only contain letters, numbers, hyphens, and underscores.',
            'password.min' => 'Password must be at least 8 characters long.',
            'github_token.regex' => 'GitHub token must be exactly 40 characters long and contain only alphanumeric characters.',
            'url.url' => 'Please provide a valid URL.',
        ];
    }

    /**
     * Get common validation attributes
     *
     * @return array<string, string>
     */
    protected function getCommonValidationAttributes(): array
    {
        return [
            'email' => 'email address',
            'username' => 'username',
            'password' => 'password',
            'name' => 'name',
            'url' => 'URL',
            'github_token' => 'GitHub token',
        ];
    }
} 