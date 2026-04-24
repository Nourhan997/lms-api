<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strip_tags($this->name ?? ''),
            'bio'  => strip_tags($this->bio ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'preferred_language' => ['required', 'in:en,ar'],
            'bio'                => ['nullable', 'string', 'max:1000'],
        ];
    }
}
