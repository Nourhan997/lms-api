<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title'          => strip_tags($this->title ?? ''),
            'title_ar'       => strip_tags($this->title_ar ?? ''),
            'description'    => strip_tags($this->description ?? ''),
            'description_ar' => strip_tags($this->description_ar ?? ''),
            'slug'           => str($this->title ?? '')->slug()->toString(),
        ]);
    }

    public function rules(): array
    {
        $courseId = $this->route('course')?->id;

        return [
            'instructor_id'    => ['sometimes', 'exists:users,id'],
            'category_id'      => ['required', 'exists:categories,id'],
            'title'            => ['required', 'string', 'max:255'],
            'title_ar'         => ['nullable', 'string', 'max:255'],
            'slug'             => ['required', 'string', Rule::unique('courses', 'slug')->ignore($courseId)],
            'description'      => ['required', 'string', 'max:5000'],
            'description_ar'   => ['nullable', 'string', 'max:5000'],
            'level'            => ['required', 'in:beginner,intermediate,advanced'],
            'language'         => ['required', 'in:en,ar,fr'],
            'price'            => ['required', 'numeric', 'min:0'],
            'currency'         => ['sometimes', 'string', 'size:3'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'thumbnail'        => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists'   => 'The selected category does not exist.',
            'slug.unique'          => 'A course with this title already exists.',
            'price.min'            => 'Price cannot be negative.',
            'level.in'             => 'Level must be beginner, intermediate, or advanced.',
            'language.in'          => 'Language must be en, ar, or fr.',
        ];
    }
}
