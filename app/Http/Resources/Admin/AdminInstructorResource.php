<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminInstructorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'bio'              => $this->bio,
            'is_active'        => $this->is_active,
            'courses_count'    => $this->courses_count ?? 0,
            'total_enrollments' => $this->total_enrollments ?? 0,
            'created_at'       => $this->created_at->format('Y-m-d'),
        ];
    }
}
