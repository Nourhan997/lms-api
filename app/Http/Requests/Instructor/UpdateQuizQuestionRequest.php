<?php

declare(strict_types=1);

namespace App\Http\Requests\Instructor;

use App\Enums\QuizQuestionType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('question')) {
            $this->merge(['question' => strip_tags($this->question ?? '')]);
        }
        if ($this->has('explanation')) {
            $this->merge(['explanation' => $this->explanation !== null ? strip_tags($this->explanation) : null]);
        }
    }

    public function rules(): array
    {
        return [
            'question'              => ['sometimes', 'string', 'max:1000'],
            'type'                  => ['sometimes', Rule::enum(QuizQuestionType::class)],
            'explanation'           => ['nullable', 'string', 'max:2000'],
            'options'               => ['sometimes', 'array', 'min:2'],
            'options.*.option_text' => ['required_with:options', 'string', 'max:500'],
            'options.*.is_correct'  => ['required_with:options', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if (!$this->has('options')) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            $options    = $this->input('options', []);
            $hasCorrect = collect($options)->contains(fn($o) => (bool) ($o['is_correct'] ?? false));
            if (!$hasCorrect) {
                $validator->errors()->add('options', 'At least one option must be marked as correct.');
            }
        });
    }
}
