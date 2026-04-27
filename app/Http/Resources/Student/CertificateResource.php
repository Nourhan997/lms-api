<?php

declare(strict_types=1);

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'certificate_uid' => $this->certificate_uid,
            'course'          => $this->whenLoaded('course', fn() => [
                'id'    => $this->course->id,
                'title' => $this->course->title,
                'slug'  => $this->course->slug,
            ]),
            'issued_at'       => $this->issued_at->format('Y-m-d'),
            'pdf_url'         => $this->pdf_path
                ? Storage::disk('public')->url($this->pdf_path)
                : null,
            'share_url'       => '/certificates/' . $this->certificate_uid,
        ];
    }
}
