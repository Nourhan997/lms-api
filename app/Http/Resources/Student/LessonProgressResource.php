<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonProgressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'lesson_id'     => $this->lesson_id,
            'enrollment_id' => $this->enrollment_id,
            'is_completed'  => $this->is_completed,
            'completed_at'  => $this->completed_at?->format('Y-m-d H:i'),
        ];
    }
}
