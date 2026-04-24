<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'  => strip_tags($this->name ?? ''),
            'email' => strip_tags($this->email ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'unique:users,email', 'max:255'],
            'password'           => ['required', 'string', 'min:8', 'confirmed'],
            'preferred_language' => ['required', 'in:en,ar'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'               => 'This email is already registered.',
            'password.confirmed'         => 'Password confirmation does not match.',
            'preferred_language.in'      => 'Preferred language must be en or ar.',
        ];
    }
}
