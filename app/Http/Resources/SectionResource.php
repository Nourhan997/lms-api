<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'course_id'    => $this->course_id,
            'title'        => $this->title,
            'title_ar'     => $this->title_ar,
            'order'        => $this->order,
            'is_published' => $this->is_published,
            'lessons_count' => $this->lessons_count ?? 0,
            'has_quiz'     => $this->whenLoaded('quiz', fn() => ! is_null($this->quiz), false),
            'created_at'   => $this->created_at->format('Y-m-d'),
        ];
    }
}
