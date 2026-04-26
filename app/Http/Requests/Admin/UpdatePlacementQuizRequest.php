<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlacementQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title'       => strip_tags($this->title ?? ''),
            'description' => strip_tags($this->description ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'subject'     => ['sometimes', 'string', 'in:english,french'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
