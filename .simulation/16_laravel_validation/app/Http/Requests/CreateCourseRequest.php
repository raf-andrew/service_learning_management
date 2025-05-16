<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CreateCourseRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Course::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:courses'],
            'slug' => ['required', 'string', 'max:255', 'unique:courses', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['required', 'string', 'min:100', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'category_id' => ['required', 'exists:categories,id'],
            'level' => ['required', 'string', Rule::in(['beginner', 'intermediate', 'advanced'])],
            'duration' => ['required', 'integer', 'min:1', 'max:1000'], // in hours
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['string', 'max:255'],
            'objectives' => ['required', 'array', 'min:1', 'max:10'],
            'objectives.*' => ['string', 'max:255'],
            'thumbnail' => ['required', 'image', 'max:2048'], // 2MB max
            'is_published' => ['boolean'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'max_students' => ['required', 'integer', 'min:1', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
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
            'title.unique' => 'A course with this title already exists.',
            'slug.regex' => 'The slug can only contain lowercase letters, numbers, and hyphens.',
            'description.min' => 'The course description must be at least 100 characters long.',
            'description.max' => 'The course description cannot exceed 5000 characters.',
            'price.min' => 'The course price must be at least 0.',
            'price.max' => 'The course price cannot exceed 9999.99.',
            'level.in' => 'The course level must be either beginner, intermediate, or advanced.',
            'duration.min' => 'The course duration must be at least 1 hour.',
            'duration.max' => 'The course duration cannot exceed 1000 hours.',
            'objectives.min' => 'The course must have at least one learning objective.',
            'objectives.max' => 'The course cannot have more than 10 learning objectives.',
            'thumbnail.max' => 'The thumbnail image must not be larger than 2MB.',
            'start_date.after' => 'The course start date must be in the future.',
            'end_date.after' => 'The course end date must be after the start date.',
            'max_students.min' => 'The maximum number of students must be at least 1.',
            'max_students.max' => 'The maximum number of students cannot exceed 1000.',
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
            'category_id' => 'category',
            'is_published' => 'publication status',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'max_students' => 'maximum number of students',
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
        // Ensure slug is lowercase and has no trailing hyphens
        if (isset($validated['slug'])) {
            $validated['slug'] = strtolower(trim($validated['slug'], '-'));
        }

        // Ensure price has exactly 2 decimal places
        if (isset($validated['price'])) {
            $validated['price'] = round($validated['price'], 2);
        }

        // Ensure tags are unique and lowercase
        if (isset($validated['tags'])) {
            $validated['tags'] = array_unique(array_map('strtolower', $validated['tags']));
        }

        // Ensure objectives are unique
        if (isset($validated['objectives'])) {
            $validated['objectives'] = array_unique($validated['objectives']);
        }

        return $validated;
    }
} 