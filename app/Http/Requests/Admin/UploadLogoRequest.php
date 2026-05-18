<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'A logo image is required',
            'logo.mimes'    => 'Logo must be a JPEG, PNG, or WebP image',
            'logo.max'      => 'Logo may not be larger than 2MB',
        ];
    }
}
