<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'role'                => $this->role,
            'is_active'           => $this->is_active,
            'preferred_language'  => $this->preferred_language,
            'enrollment_count'    => $this->enrollments_count ?? 0,
            'placement_completed' => $this->placement_completed_at !== null,
            'created_at'          => $this->created_at->format('Y-m-d'),
        ];
    }
}
