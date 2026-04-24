<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;

class CourseDetailResource extends CourseListResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'description'    => $this->description,
            'description_ar' => $this->description_ar,
            'sections'       => $this->whenLoaded('sections', fn() =>
                $this->sections->map(fn($section) => [
                    'id'            => $section->id,
                    'title'         => $section->title,
                    'title_ar'      => $section->title_ar,
                    'order'         => $section->order,
                    'lessons_count' => $section->lessons->count(),
                    'has_quiz'      => $section->relationLoaded('quiz') && $section->quiz !== null,
                ])
            ),
        ]);
    }
}
