<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseFormRequest
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
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:2048'], // 2MB max
            'preferences' => ['nullable', 'array'],
            'preferences.notifications' => ['nullable', 'boolean'],
            'preferences.theme' => ['nullable', 'string', 'in:light,dark,system'],
            'preferences.language' => ['nullable', 'string', 'size:2'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return array_merge(parent::messages(), [
            'phone.regex' => 'The phone number must be in a valid international format.',
            'avatar.max' => 'The avatar image must not be larger than 2MB.',
            'preferences.theme.in' => 'The theme must be either light, dark, or system.',
            'preferences.language.size' => 'The language code must be exactly 2 characters.',
        ]);
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return array_merge(parent::attributes(), [
            'bio' => 'biography',
            'preferences.notifications' => 'notification preferences',
            'preferences.theme' => 'theme preference',
            'preferences.language' => 'language preference',
        ]);
    }

    /**
     * Transform the validated data after validation.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function transformValidatedData(array $validated)
    {
        // Ensure phone number is in E.164 format
        if (isset($validated['phone'])) {
            $validated['phone'] = $this->formatPhoneNumber($validated['phone']);
        }

        // Ensure language code is lowercase
        if (isset($validated['preferences']['language'])) {
            $validated['preferences']['language'] = strtolower($validated['preferences']['language']);
        }

        return $validated;
    }

    /**
     * Format phone number to E.164 format.
     *
     * @param  string  $phone
     * @return string
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add + if not present
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
} 