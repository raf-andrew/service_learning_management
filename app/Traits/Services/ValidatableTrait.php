<?php

namespace App\Traits\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

/**
 * Validatable Trait
 * 
 * Provides validation functionality for services.
 * Implements comprehensive validation with custom rules and error handling.
 */
trait ValidatableTrait
{
    /**
     * @var array
     */
    protected array $validationRules = [];

    /**
     * @var array
     */
    protected array $customValidationRules = [];

    /**
     * @var array
     */
    protected array $validationMessages = [];

    /**
     * @var array
     */
    protected array $validationAttributes = [];

    /**
     * Initialize validation
     */
    protected function initializeValidation(): void
    {
        $this->loadValidationRules();
        $this->loadCustomValidationRules();
        $this->loadValidationMessages();
        $this->loadValidationAttributes();
    }

    /**
     * Load validation rules from configuration
     */
    protected function loadValidationRules(): void
    {
        $configKey = "validation.{$this->getServiceName()}";
        $this->validationRules = config($configKey . '.rules', []);
    }

    /**
     * Load custom validation rules
     */
    protected function loadCustomValidationRules(): void
    {
        $configKey = "validation.{$this->getServiceName()}";
        $this->customValidationRules = config($configKey . '.custom_rules', []);
    }

    /**
     * Load validation messages
     */
    protected function loadValidationMessages(): void
    {
        $configKey = "validation.{$this->getServiceName()}";
        $this->validationMessages = config($configKey . '.messages', []);
    }

    /**
     * Load validation attributes
     */
    protected function loadValidationAttributes(): void
    {
        $configKey = "validation.{$this->getServiceName()}";
        $this->validationAttributes = config($configKey . '.attributes', []);
    }

    /**
     * Validate data against rules
     */
    protected function validateData(array $data, array $rules = [], array $messages = [], array $attributes = []): array
    {
        $validationRules = array_merge($this->validationRules, $rules);
        $validationMessages = array_merge($this->validationMessages, $messages);
        $validationAttributes = array_merge($this->validationAttributes, $attributes);

        if (empty($validationRules)) {
            return $data;
        }

        $validator = Validator::make($data, $validationRules, $validationMessages, $validationAttributes);

        // Add custom validation rules
        $this->addCustomValidationRules($validator);

        if ($validator->fails()) {
            $this->logValidationFailure($validator->errors()->toArray(), $data);
            throw new ValidationException($validator);
        }

        $this->logValidationSuccess($data);
        return $validator->validated();
    }

    /**
     * Add custom validation rules to validator
     */
    protected function addCustomValidationRules($validator): void
    {
        foreach ($this->customValidationRules as $rule => $callback) {
            $validator->addExtension($rule, $callback);
        }
    }

    /**
     * Validate single field
     */
    protected function validateField(string $field, $value, array $rules = []): bool
    {
        $data = [$field => $value];
        $fieldRules = [$field => $rules];

        try {
            $this->validateData($data, $fieldRules);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Validate multiple fields
     */
    protected function validateFields(array $fields, array $rules = []): array
    {
        $data = [];
        $fieldRules = [];

        foreach ($fields as $field => $value) {
            $data[$field] = $value;
            if (isset($rules[$field])) {
                $fieldRules[$field] = $rules[$field];
            }
        }

        return $this->validateData($data, $fieldRules);
    }

    /**
     * Validate model data
     */
    protected function validateModelData(array $data, string $modelClass, array $additionalRules = []): array
    {
        $modelRules = $this->getModelValidationRules($modelClass);
        $rules = array_merge($modelRules, $additionalRules);

        return $this->validateData($data, $rules);
    }

    /**
     * Get model validation rules
     */
    protected function getModelValidationRules(string $modelClass): array
    {
        if (method_exists($modelClass, 'getValidationRules')) {
            return $modelClass::getValidationRules();
        }

        return config("validation.models.{$modelClass}", []);
    }

    /**
     * Validate array of items
     */
    protected function validateArray(array $items, array $rules, string $itemName = 'item'): array
    {
        $validatedItems = [];

        foreach ($items as $index => $item) {
            try {
                $validatedItems[] = $this->validateData($item, $rules);
            } catch (ValidationException $e) {
                $this->logValidationFailure($e->errors(), $item, "{$itemName}[{$index}]");
                throw $e;
            }
        }

        return $validatedItems;
    }

    /**
     * Validate conditional data
     */
    protected function validateConditionalData(array $data, array $conditions): array
    {
        foreach ($conditions as $condition => $rules) {
            if ($this->evaluateCondition($condition, $data)) {
                $data = $this->validateData($data, $rules);
            }
        }

        return $data;
    }

    /**
     * Evaluate validation condition
     */
    protected function evaluateCondition(string $condition, array $data): bool
    {
        // Simple condition evaluation - can be extended for complex conditions
        if (strpos($condition, 'required_if:') === 0) {
            $parts = explode(':', $condition);
            $field = $parts[1];
            $value = $parts[2] ?? null;
            
            return isset($data[$field]) && $data[$field] == $value;
        }

        if (strpos($condition, 'required_with:') === 0) {
            $parts = explode(':', $condition);
            $field = $parts[1];
            
            return isset($data[$field]) && !empty($data[$field]);
        }

        return true;
    }

    /**
     * Sanitize data
     */
    protected function sanitizeData(array $data, array $sanitizationRules = []): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (isset($sanitizationRules[$key])) {
                $sanitized[$key] = $this->applySanitizationRule($value, $sanitizationRules[$key]);
            } else {
                $sanitized[$key] = $this->applyDefaultSanitization($value);
            }
        }

        return $sanitized;
    }

    /**
     * Apply sanitization rule
     */
    protected function applySanitizationRule($value, string $rule): mixed
    {
        switch ($rule) {
            case 'trim':
                return is_string($value) ? trim($value) : $value;
            case 'lowercase':
                return is_string($value) ? strtolower($value) : $value;
            case 'uppercase':
                return is_string($value) ? strtoupper($value) : $value;
            case 'strip_tags':
                return is_string($value) ? strip_tags($value) : $value;
            case 'htmlspecialchars':
                return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            case 'integer':
                return is_numeric($value) ? (int) $value : $value;
            case 'float':
                return is_numeric($value) ? (float) $value : $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            default:
                return $value;
        }
    }

    /**
     * Apply default sanitization
     */
    protected function applyDefaultSanitization($value): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Get validation errors as array
     */
    protected function getValidationErrors(ValidationException $e): array
    {
        return [
            'message' => 'Validation failed',
            'errors' => $e->errors(),
            'service' => $this->getServiceName()
        ];
    }

    /**
     * Log validation success
     */
    protected function logValidationSuccess(array $data): void
    {
        Log::debug("Validation success", [
            'service' => $this->getServiceName(),
            'data_keys' => array_keys($data),
            'data_count' => count($data)
        ]);
    }

    /**
     * Log validation failure
     */
    protected function logValidationFailure(array $errors, array $data, string $context = ''): void
    {
        Log::warning("Validation failure", [
            'service' => $this->getServiceName(),
            'context' => $context,
            'errors' => $errors,
            'data_keys' => array_keys($data)
        ]);
    }

    /**
     * Get validation statistics
     */
    public function getValidationStatistics(): array
    {
        return [
            'rules_count' => count($this->validationRules),
            'custom_rules_count' => count($this->customValidationRules),
            'messages_count' => count($this->validationMessages),
            'attributes_count' => count($this->validationAttributes)
        ];
    }

    /**
     * Get service name for validation configuration
     */
    abstract protected function getServiceName(): string;
} 