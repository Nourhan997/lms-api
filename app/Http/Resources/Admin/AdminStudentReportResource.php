<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminStudentReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'enrollment_count' => $this->enrollments_count ?? 0,
            'completed_count'  => $this->completed_count ?? 0,
            'total_spent'      => (float) ($this->total_spent ?? 0),
            'last_active'      => $this->updated_at->format('Y-m-d'),
        ];
    }
}
