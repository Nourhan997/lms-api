<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['platform_name', 'platform_tagline', 'from_name', 'footer_text'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => strip_tags($this->input($field, ''))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'platform_name'              => ['sometimes', 'string', 'max:255'],
            'platform_tagline'           => ['sometimes', 'string', 'max:500'],
            'support_email'              => ['sometimes', 'email'],
            'primary_color'              => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color'            => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'from_name'                  => ['sometimes', 'string', 'max:255'],
            'from_address'               => ['sometimes', 'email'],
            'footer_text'                => ['sometimes', 'string', 'max:1000'],
            'default_language'           => ['sometimes', 'in:en,ar'],
            'available_languages'        => ['sometimes', 'string'],
            'default_currency'           => ['sometimes', 'string', 'max:3'],
            'allow_self_registration'    => ['sometimes', 'string', 'in:true,false'],
            'require_email_verification' => ['sometimes', 'string', 'in:true,false'],
            'placement_test_required'    => ['sometimes', 'string', 'in:true,false'],
        ];
    }

    public function messages(): array
    {
        return [
            'primary_color.regex'   => 'Primary color must be a valid hex color (e.g. #1A3A5C)',
            'secondary_color.regex' => 'Secondary color must be a valid hex color (e.g. #2E75B6)',
            'support_email.email'   => 'Support email must be a valid email address',
            'from_address.email'    => 'From address must be a valid email address',
        ];
    }
}
