<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge(['title' => strip_tags($this->title ?? '')]);
        }
    }

    public function rules(): array
    {
        return [
            'title'      => ['sometimes', 'string', 'max:255'],
            'pass_score' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
