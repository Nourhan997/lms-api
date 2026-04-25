<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'score'        => $this->score,
            'passed'       => $this->passed,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'answers'      => $this->whenLoaded('answers', fn() => $this->answers->map(fn($a) => [
                'question_id'        => $a->quiz_question_id,
                'is_correct'         => $a->is_correct,
                'selected_option_id' => $a->selected_option_id,
                'text_answer'        => $a->text_answer,
            ])),
        ];
    }
}
