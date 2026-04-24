<?php

declare(strict_types=1);

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\Enrollment;
use Illuminate\Support\Str;

class CertificateService
{
    public function issue(Enrollment $enrollment): Certificate
    {
        return Certificate::create([
            'user_id'         => $enrollment->user_id,
            'course_id'       => $enrollment->course_id,
            'enrollment_id'   => $enrollment->id,
            'certificate_uid' => Str::uuid()->toString(),
            'issued_at'       => now(),
        ]);
    }
}
