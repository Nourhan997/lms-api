<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LessonContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'lesson_id'  => $this->lesson_id,
            'type'       => $this->type,
            'content'    => $this->content,
            'file_url'   => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
            'order'      => $this->order,
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
