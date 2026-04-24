<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'status'              => $this->status,
            'enrolled_at'         => $this->enrolled_at->format('Y-m-d H:i'),
            'completed_at'        => $this->completed_at?->format('Y-m-d H:i'),
            'progress_percentage' => $this->computeProgressPercentage(),
            'course'              => $this->whenLoaded('course', fn() => [
                'id'        => $this->course->id,
                'title'     => $this->course->title,
                'slug'      => $this->course->slug,
                'thumbnail' => $this->course->thumbnail,
                'level'     => $this->course->level,
                'language'  => $this->course->language,
                'instructor' => $this->course->relationLoaded('instructor')
                    ? ['id' => $this->course->instructor->id, 'name' => $this->course->instructor->name]
                    : null,
                'category'  => $this->course->relationLoaded('category')
                    ? ['id' => $this->course->category->id, 'name' => $this->course->category->name]
                    : null,
            ]),
        ];
    }

    private function computeProgressPercentage(): ?float
    {
        $completed = $this->resource->completed_lessons_count ?? null;
        $total     = $this->resource->course->lessons_count ?? null;

        if ($completed === null || $total === null) {
            return null;
        }

        return $total > 0 ? round($completed / $total * 100, 2) : 0.0;
    }
}
