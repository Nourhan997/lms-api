<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        if ($this->has('title')) {
            $merges['title'] = strip_tags($this->title ?? '');
        }

        if ($this->has('title_ar')) {
            $merges['title_ar'] = strip_tags($this->title_ar ?? '');
        }

        if (!empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'string', 'max:255'],
            'title_ar'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'body'         => ['sometimes', 'string'],
            'body_ar'      => ['sometimes', 'nullable', 'string'],
            'thumbnail'    => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
