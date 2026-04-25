<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use App\Enums\QuizQuestionType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'question'    => strip_tags($this->question ?? ''),
            'explanation' => $this->explanation !== null ? strip_tags($this->explanation) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'question'              => ['required', 'string', 'max:1000'],
            'type'                  => ['required', Rule::enum(QuizQuestionType::class)],
            'explanation'           => ['nullable', 'string', 'max:2000'],
            'options'               => ['required', 'array', 'min:2'],
            'options.*.option_text' => ['required', 'string', 'max:500'],
            'options.*.is_correct'  => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $options    = $this->input('options', []);
            $hasCorrect = collect($options)->contains(fn($o) => (bool) ($o['is_correct'] ?? false));
            if (!$hasCorrect) {
                $validator->errors()->add('options', 'At least one option must be marked as correct.');
            }
        });
    }
}
