<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadFaviconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'favicon' => ['required', 'file', 'mimes:ico,png', 'max:512'],
        ];
    }

    public function messages(): array
    {
        return [
            'favicon.required' => 'A favicon file is required',
            'favicon.mimes'    => 'Favicon must be an ICO or PNG file',
            'favicon.max'      => 'Favicon may not be larger than 512KB',
        ];
    }
}
