<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type') ?? $this->route('content')?->type?->value;

        $base = ['type' => ['sometimes', 'in:video,audio,pdf,text']];

        return match ($type) {
            'video' => array_merge($base, [
                'content' => ['sometimes', 'required', 'url', 'max:2048'],
            ]),
            'audio' => array_merge($base, [
                'file' => ['sometimes', 'file', 'mimes:mp3,wav', 'max:51200'],
            ]),
            'pdf'   => array_merge($base, [
                'file' => ['sometimes', 'file', 'mimes:pdf', 'max:20480'],
            ]),
            'text'  => array_merge($base, [
                'content' => ['sometimes', 'required', 'string'],
            ]),
            default => $base,
        };
    }
}
