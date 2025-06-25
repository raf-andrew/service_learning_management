<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait ValidationTrait
{
    /**
     * Validate request data and return errors if validation fails
     */
    protected function validateRequest(Request $request, array $rules, array $messages = []): ?JsonResponse
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        return null;
    }

    /**
     * Validate request data and return validated data or error response
     */
    protected function validateAndGetData(Request $request, array $rules, array $messages = []): array|JsonResponse
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        return $validator->validated();
    }

    /**
     * Common validation rules
     */
    protected function getCommonValidationRules(): array
    {
        return [
            'pagination' => [
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ],
            'date_range' => [
                'from' => 'nullable|date',
                'to' => 'nullable|date|after_or_equal:from'
            ],
            'search' => [
                'search' => 'nullable|string|max:255',
                'sort_by' => 'nullable|string|in:created_at,updated_at,name,id',
                'sort_order' => 'nullable|string|in:asc,desc'
            ]
        ];
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(strip_tags($value));
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Validate and sanitize request data
     */
    protected function validateAndSanitize(Request $request, array $rules, array $messages = []): array|JsonResponse
    {
        $validationResult = $this->validateAndGetData($request, $rules, $messages);
        
        if ($validationResult instanceof JsonResponse) {
            return $validationResult;
        }

        return $this->sanitizeInput($validationResult);
    }
} 