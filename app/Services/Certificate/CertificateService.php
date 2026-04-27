<?php

declare(strict_types=1);

namespace App\Services\Certificate;

use App\Enums\EnrollmentStatus;
use App\Exceptions\CertificateNotFoundException;
use App\Jobs\GenerateCertificatePdf;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CertificateService
{
    public function issue(Enrollment $enrollment): Certificate
    {
        if ($enrollment->status !== EnrollmentStatus::Completed) {
            throw new \InvalidArgumentException('Enrollment must be completed to issue a certificate.');
        }

        $enrollment->loadMissing('certificate');

        if ($enrollment->certificate) {
            return $enrollment->certificate;
        }

        $uid = strtoupper(Str::uuid()->toString());

        $certificate = Certificate::create([
            'user_id'         => $enrollment->user_id,
            'course_id'       => $enrollment->course_id,
            'enrollment_id'   => $enrollment->id,
            'certificate_uid' => $uid,
            'issued_at'       => now(),
        ]);

        GenerateCertificatePdf::dispatch($certificate);

        return $certificate;
    }

    public function verify(string $uid): Certificate
    {
        $certificate = Certificate::where('certificate_uid', $uid)
            ->with(['user', 'course.instructor'])
            ->first();

        if (!$certificate) {
            throw new CertificateNotFoundException();
        }

        return $certificate;
    }

    public function getForUser(User $user): Collection
    {
        return $user->certificates()->with('course')->get();
    }

    public function getByUid(string $uid): ?Certificate
    {
        return Certificate::where('certificate_uid', $uid)->first();
    }
}
