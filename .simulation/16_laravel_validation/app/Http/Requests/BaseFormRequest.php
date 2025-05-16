<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    abstract public function rules();

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'unique' => 'The :attribute has already been taken.',
            'confirmed' => 'The :attribute confirmation does not match.',
            'date' => 'The :attribute is not a valid date.',
            'numeric' => 'The :attribute must be a number.',
            'array' => 'The :attribute must be an array.',
            'string' => 'The :attribute must be a string.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [
            'email' => 'email address',
            'password' => 'password',
            'name' => 'full name',
            'phone' => 'phone number',
            'address' => 'street address',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        
        // Log validation errors
        Log::warning('Validation failed', [
            'request' => $this->all(),
            'errors' => $errors->toArray(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);

        throw new ValidationException($validator);
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Trim all string inputs
        $this->merge(array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->all()));
    }

    /**
     * Get the validated data from the request.
     *
     * @return array<string, mixed>
     */
    public function validated()
    {
        $validated = parent::validated();

        // Apply any post-validation transformations
        return $this->transformValidatedData($validated);
    }

    /**
     * Transform the validated data after validation.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function transformValidatedData(array $validated)
    {
        return $validated;
    }
} 