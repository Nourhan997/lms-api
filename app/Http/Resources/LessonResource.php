<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'section_id'       => $this->section_id,
            'title'            => $this->title,
            'title_ar'         => $this->title_ar,
            'order'            => $this->order,
            'is_published'     => $this->is_published,
            'duration_minutes' => $this->duration_minutes,
            'contents_count'   => $this->contents_count ?? 0,
            'created_at'       => $this->created_at->format('Y-m-d'),
        ];
    }
}
