<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title'    => strip_tags($this->title ?? ''),
            'title_ar' => strip_tags($this->title_ar ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'title_ar'     => ['nullable', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
