<?php

namespace App\Http\Requests\DeveloperCredential;

use App\Http\Requests\BaseFormRequest;

/**
 * Create Developer Credential Request
 * 
 * Handles validation for creating new developer credentials.
 */
class CreateDeveloperCredentialRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'github_username' => 'required|string|min:1|max:39|regex:/^[a-zA-Z0-9-]+$/',
            'github_token' => 'required|string|regex:/^[a-zA-Z0-9]{40}$/',
            'permissions' => 'nullable|array',
            'permissions.repo' => 'nullable|boolean',
            'permissions.admin' => 'nullable|boolean',
            'permissions.delete_repo' => 'nullable|boolean',
            'permissions.workflow' => 'nullable|boolean',
            'permissions.write' => 'nullable|boolean',
            'permissions.read' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'github_username.regex' => 'GitHub username can only contain letters, numbers, and hyphens.',
            'github_token.regex' => 'GitHub token must be exactly 40 characters long and contain only alphanumeric characters.',
            'expires_at.after' => 'Expiration date must be in the future.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.boolean' => 'Permission values must be true or false.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'github_username' => 'GitHub username',
            'github_token' => 'GitHub token',
            'permissions' => 'permissions',
            'permissions.repo' => 'repository permission',
            'permissions.admin' => 'admin permission',
            'permissions.delete_repo' => 'delete repository permission',
            'permissions.workflow' => 'workflow permission',
            'permissions.write' => 'write permission',
            'permissions.read' => 'read permission',
            'expires_at' => 'expiration date',
            'description' => 'description',
            'is_active' => 'active status',
        ]);
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'github_username' => strtolower(trim($this->github_username ?? '')),
            'github_token' => trim($this->github_token ?? ''),
            'description' => trim($this->description ?? ''),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }

    /**
     * Get validated data with defaults
     *
     * @return array<string, mixed>
     */
    public function getValidatedDataWithDefaults(): array
    {
        $data = $this->validated();
        
        // Set default permissions if not provided
        if (!isset($data['permissions'])) {
            $data['permissions'] = [
                'repo' => true,
                'admin' => false,
                'delete_repo' => false,
                'workflow' => true,
                'write' => true,
                'read' => true,
            ];
        }
        
        // Set default active status
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }
        
        return $data;
    }
} 