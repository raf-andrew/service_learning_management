<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * Base Form Request
 * 
 * Provides common functionality for all form requests including
 * validation, error handling, and response formatting.
 */
abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->getCommonValidationMessages();
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->getCommonValidationAttributes();
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'timestamp' => now()->toISOString(),
                ], 422)
            );
        }

        throw new ValidationException($validator);
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedAuthorization(): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'timestamp' => now()->toISOString(),
                ], 403)
            );
        }

        abort(403, 'Unauthorized');
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return $this->sanitizeInput($this->all());
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
     * Get common validation messages
     *
     * @return array<string, string>
     */
    protected function getCommonValidationMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'string' => 'The :attribute must be a string.',
            'integer' => 'The :attribute must be an integer.',
            'numeric' => 'The :attribute must be a number.',
            'boolean' => 'The :attribute must be true or false.',
            'array' => 'The :attribute must be an array.',
            'min' => [
                'string' => 'The :attribute must be at least :min characters.',
                'numeric' => 'The :attribute must be at least :min.',
                'array' => 'The :attribute must have at least :min items.',
            ],
            'max' => [
                'string' => 'The :attribute may not be greater than :max characters.',
                'numeric' => 'The :attribute may not be greater than :max.',
                'array' => 'The :attribute may not have more than :max items.',
            ],
            'unique' => 'The :attribute has already been taken.',
            'exists' => 'The selected :attribute is invalid.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'regex' => 'The :attribute format is invalid.',
            'url' => 'The :attribute format is invalid.',
            'date' => 'The :attribute is not a valid date.',
            'date_format' => 'The :attribute does not match the format :format.',
            'in' => 'The selected :attribute is invalid.',
            'not_in' => 'The selected :attribute is invalid.',
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
            'title' => 'title',
            'description' => 'description',
            'content' => 'content',
            'url' => 'URL',
            'github_token' => 'GitHub token',
            'github_username' => 'GitHub username',
        ];
    }

    /**
     * Get validated data with optional fields
     *
     * @param array<string> $fields
     * @return array<string, mixed>
     */
    public function getValidatedData(array $fields = []): array
    {
        $validated = $this->validated();
        
        if (empty($fields)) {
            return $validated;
        }
        
        return array_intersect_key($validated, array_flip($fields));
    }

    /**
     * Get a single validated field
     *
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function getValidatedField(string $field, $default = null)
    {
        $validated = $this->validated();
        
        return $validated[$field] ?? $default;
    }

    /**
     * Check if request expects JSON response
     *
     * @return bool
     */
    protected function expectsJson(): bool
    {
        return $this->expectsJson || $this->is('api/*') || $this->wantsJson();
    }
} 