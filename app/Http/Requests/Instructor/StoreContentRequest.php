<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $base = ['type' => ['required', 'in:video,audio,pdf,text']];

        return match ($this->input('type')) {
            'video' => array_merge($base, [
                'content' => ['required', 'url', 'max:2048'],
            ]),
            'audio' => array_merge($base, [
                'file' => ['required', 'file', 'mimes:mp3,wav', 'max:51200'],
            ]),
            'pdf'   => array_merge($base, [
                'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            ]),
            'text'  => array_merge($base, [
                'content' => ['required', 'string'],
            ]),
            default => $base,
        };
    }

    public function messages(): array
    {
        return [
            'type.in'     => 'Content type must be video, audio, pdf, or text.',
            'file.mimes'  => 'Audio files must be mp3 or wav. PDF must be a valid pdf.',
            'file.max'    => 'Audio files may not exceed 50MB. PDFs may not exceed 20MB.',
            'content.url' => 'Video content must be a valid URL.',
        ];
    }
}
