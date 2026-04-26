<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlacementQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'question' => strip_tags($this->question ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'question'             => ['sometimes', 'string', 'max:1000'],
            'type'                 => ['sometimes', 'string', 'in:multiple_choice,true_false'],
            'options'              => ['sometimes', 'array', 'min:2'],
            'options.*.option_text' => ['required_with:options', 'string', 'max:500'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $options = $this->input('options');
            if ($options === null) {
                return;
            }
            $hasCorrect = collect($options)->contains(fn($o) => (bool) ($o['is_correct'] ?? false));
            if (!$hasCorrect) {
                $validator->errors()->add('options', 'At least one option must be marked as correct.');
            }
        });
    }
}
