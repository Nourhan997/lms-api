<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlacementResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => strip_tags($this->label ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'score_min' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'score_max' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'label'     => ['sometimes', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = $this->input('score_min');
            $max = $this->input('score_max');
            if ($min !== null && $max !== null && (int) $min >= (int) $max) {
                $validator->errors()->add('score_min', 'score_min must be less than score_max.');
            }
        });
    }
}
