<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'title_ar'         => $this->title_ar,
            'slug'             => $this->slug,
            'description'      => $this->description,
            'description_ar'   => $this->description_ar,
            'thumbnail'        => $this->thumbnail,
            'level'            => $this->level,
            'language'         => $this->language,
            'price'            => $this->price,
            'currency'         => $this->currency,
            'is_free'          => $this->price == 0,
            'status'           => $this->status,
            'duration_minutes' => $this->duration_minutes,
            'next_course_id'   => $this->next_course_id,
            'enrollment_count' => $this->enrollments_count ?? 0,
            'completion_count' => $this->completed_enrollments_count ?? 0,
            'revenue'          => $this->payments_sum_amount ?? 0,
            'created_at'       => $this->created_at->format('Y-m-d'),
            'updated_at'       => $this->updated_at->format('Y-m-d'),
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
