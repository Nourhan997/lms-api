<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlogPostRequest extends FormRequest
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
            'title'        => ['required', 'string', 'max:255'],
            'title_ar'     => ['nullable', 'string', 'max:255'],
            'body'         => ['required', 'string'],
            'body_ar'      => ['nullable', 'string'],
            'thumbnail'    => ['nullable', 'string', 'max:500'],
            'is_published' => ['boolean'],
        ];
    }
}
