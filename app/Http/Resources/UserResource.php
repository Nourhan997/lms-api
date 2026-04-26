<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\Student\CourseListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'role'               => $this->role,
            'avatar'             => $this->avatar,
            'bio'                => $this->bio,
            'preferred_language' => $this->preferred_language,
            'is_active'          => $this->is_active,
            'email_verified_at'  => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'created_at'         => $this->created_at->format('Y-m-d'),
            'placement_completed' => $this->placement_completed_at !== null,
            'suggested_course'    => $this->whenLoaded(
                'suggestedCourse',
                fn() => $this->suggestedCourse ? new CourseListResource($this->suggestedCourse) : null,
            ),
        ];
    }
}
