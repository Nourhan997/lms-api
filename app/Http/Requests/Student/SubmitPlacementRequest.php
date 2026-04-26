<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers'              => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:placement_quiz_questions,id'],
            'answers.*.option_id'  => ['required', 'integer', 'exists:placement_quiz_options,id'],
        ];
    }
}
