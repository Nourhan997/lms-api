<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Student\CourseListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlacementScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            'email'                  => $this->email,
            'placement_score'        => $this->placement_score,
            'placement_label'        => $this->placement_label,
            'placement_completed_at' => $this->placement_completed_at?->format('Y-m-d H:i'),
            'suggested_course'       => $this->whenLoaded(
                'suggestedCourse',
                fn() => $this->suggestedCourse ? new CourseListResource($this->suggestedCourse) : null,
            ),
        ];
    }
}
