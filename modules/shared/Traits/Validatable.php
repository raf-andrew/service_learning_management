<?php

namespace Modules\Shared\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

trait Validatable
{
    /**
     * Validation rules for this model/service
     */
    protected array $validationRules = [];

    /**
     * Custom validation messages
     */
    protected array $validationMessages = [];

    /**
     * Custom validation attributes
     */
    protected array $validationAttributes = [];

    /**
     * Validate data against rules
     */
    public function validate(array $data, array $rules = [], array $messages = [], array $attributes = []): array
    {
        $rules = array_merge($this->validationRules, $rules);
        $messages = array_merge($this->validationMessages, $messages);
        $attributes = array_merge($this->validationAttributes, $attributes);

        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate data without throwing exception
     */
    public function validateSilent(array $data, array $rules = [], array $messages = [], array $attributes = []): array
    {
        $rules = array_merge($this->validationRules, $rules);
        $messages = array_merge($this->validationMessages, $messages);
        $attributes = array_merge($this->validationAttributes, $attributes);

        $validator = Validator::make($data, $rules, $messages, $attributes);

        return [
            'valid' => $validator->passes(),
            'errors' => $validator->errors()->toArray(),
            'data' => $validator->validated(),
        ];
    }

    /**
     * Validate single field
     */
    public function validateField(string $field, mixed $value, array $rules = []): bool
    {
        $data = [$field => $value];
        $fieldRules = [$field => $rules];

        try {
            $this->validate($data, $fieldRules);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Get validation errors for a field
     */
    public function getFieldErrors(string $field, mixed $value, array $rules = []): array
    {
        $data = [$field => $value];
        $fieldRules = [$field => $rules];

        $result = $this->validateSilent($data, $fieldRules);
        return $result['errors'][$field] ?? [];
    }

    /**
     * Set validation rules
     */
    public function setValidationRules(array $rules): void
    {
        $this->validationRules = $rules;
    }

    /**
     * Add validation rule for field
     */
    public function addValidationRule(string $field, string $rule): void
    {
        if (!isset($this->validationRules[$field])) {
            $this->validationRules[$field] = [];
        }

        if (is_array($this->validationRules[$field])) {
            $this->validationRules[$field][] = $rule;
        } else {
            $this->validationRules[$field] = [$this->validationRules[$field], $rule];
        }
    }

    /**
     * Set validation messages
     */
    public function setValidationMessages(array $messages): void
    {
        $this->validationMessages = $messages;
    }

    /**
     * Set validation attributes
     */
    public function setValidationAttributes(array $attributes): void
    {
        $this->validationAttributes = $attributes;
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Get validation messages
     */
    public function getValidationMessages(): array
    {
        return $this->validationMessages;
    }

    /**
     * Get validation attributes
     */
    public function getValidationAttributes(): array
    {
        return $this->validationAttributes;
    }

    /**
     * Common validation rules
     */
    protected function getCommonRules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'url' => ['nullable', 'url'],
            'date' => ['nullable', 'date'],
            'numeric' => ['nullable', 'numeric'],
            'integer' => ['nullable', 'integer'],
            'boolean' => ['nullable', 'boolean'],
            'array' => ['nullable', 'array'],
            'string' => ['nullable', 'string'],
            'file' => ['nullable', 'file'],
            'image' => ['nullable', 'image'],
            'uuid' => ['nullable', 'uuid'],
        ];
    }

    /**
     * Common validation messages
     */
    protected function getCommonMessages(): array
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'unique' => 'The :attribute has already been taken.',
            'exists' => 'The selected :attribute is invalid.',
            'url' => 'The :attribute format is invalid.',
            'date' => 'The :attribute is not a valid date.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'boolean' => 'The :attribute field must be true or false.',
            'array' => 'The :attribute must be an array.',
            'string' => 'The :attribute must be a string.',
            'file' => 'The :attribute must be a file.',
            'image' => 'The :attribute must be an image.',
            'uuid' => 'The :attribute must be a valid UUID.',
        ];
    }

    /**
     * Common validation attributes
     */
    protected function getCommonAttributes(): array
    {
        return [
            'email' => 'email address',
            'password' => 'password',
            'name' => 'name',
            'phone' => 'phone number',
            'url' => 'URL',
            'date' => 'date',
            'numeric' => 'number',
            'integer' => 'integer',
            'boolean' => 'boolean value',
            'array' => 'array',
            'string' => 'string',
            'file' => 'file',
            'image' => 'image',
            'uuid' => 'UUID',
        ];
    }

    /**
     * Sanitize input data
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(strip_tags($value));
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Validate and sanitize data
     */
    public function validateAndSanitize(array $data, array $rules = [], array $messages = [], array $attributes = []): array
    {
        $sanitized = $this->sanitize($data);
        return $this->validate($sanitized, $rules, $messages, $attributes);
    }
} 