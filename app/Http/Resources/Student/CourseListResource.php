<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'title_ar'         => $this->title_ar,
            'slug'             => $this->slug,
            'thumbnail'        => $this->thumbnail,
            'level'            => $this->level,
            'language'         => $this->language,
            'price'            => $this->price,
            'currency'         => $this->currency,
            'is_free'          => $this->price == 0,
            'status'           => $this->status,
            'duration_minutes' => $this->duration_minutes,
            'enrollment_count' => $this->enrollments_count ?? 0,
            'created_at'       => $this->created_at->format('Y-m-d'),
            'instructor'       => $this->whenLoaded('instructor', fn() => [
                'id'   => $this->instructor->id,
                'name' => $this->instructor->name,
            ]),
            'category'         => $this->whenLoaded('category', fn() => [
                'id'      => $this->category->id,
                'name'    => $this->category->name,
                'name_ar' => $this->category->name_ar,
            ]),
        ];
    }
}
