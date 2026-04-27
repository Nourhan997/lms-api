<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uid'             => $this->certificate_uid,
            'student_name'    => $this->user->name,
            'course_name'     => $this->course->title,
            'instructor_name' => $this->course->instructor?->name ?? 'LMS Platform',
            'issued_at'       => $this->issued_at->format('Y-m-d'),
            'is_valid'        => true,
        ];
    }
}
