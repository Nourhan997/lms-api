<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'section_id'      => $this->section_id,
            'title'           => $this->title,
            'pass_score'      => $this->pass_score,
            'is_published'    => $this->is_published,
            'questions_count' => $this->questions_count ?? ($this->relationLoaded('questions') ? $this->questions->count() : 0),
            'questions'       => $this->whenLoaded('questions', fn() => $this->questions->map(fn($q) => [
                'id'       => $q->id,
                'question' => $q->question,
                'type'     => $q->type,
                'order'    => $q->order,
                'options'  => $q->relationLoaded('options') ? $q->options->map(fn($o) => [
                    'id'          => $o->id,
                    'option_text' => $o->option_text,
                    'order'       => $o->order,
                ]) : [],
            ])),
        ];
    }
}
