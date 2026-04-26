<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPlacementQuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'subject'         => $this->subject,
            'description'     => $this->description,
            'is_active'       => $this->is_active,
            'questions_count' => $this->questions_count ?? ($this->relationLoaded('questions') ? $this->questions->count() : 0),
            'questions'       => $this->whenLoaded('questions', fn() => $this->questions->map(fn($q) => [
                'id'       => $q->id,
                'question' => $q->question,
                'type'     => $q->type,
                'order'    => $q->order,
                'options'  => $q->relationLoaded('options') ? $q->options->map(fn($o) => [
                    'id'          => $o->id,
                    'option_text' => $o->option_text,
                    'is_correct'  => $o->is_correct,
                    'order'       => $o->order,
                ]) : [],
            ])),
            'results'         => $this->whenLoaded('results', fn() => $this->results->map(fn($r) => [
                'id'        => $r->id,
                'score_min' => $r->score_min,
                'score_max' => $r->score_max,
                'label'     => $r->label,
                'course_id' => $r->course_id,
            ])),
        ];
    }
}
