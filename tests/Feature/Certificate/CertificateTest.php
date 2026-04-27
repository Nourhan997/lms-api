<?php

declare(strict_types=1);

namespace Tests\Feature\Certificate;

use App\Jobs\GenerateCertificatePdf;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithToken(): array
    {
        $student = User::factory()->student()->create();
        $token   = $student->createToken('auth-token')->plainTextToken;

        return [$student, $token];
    }

    private function makeCompletedEnrollment(User $student): array
    {
        $course     = Course::factory()->published()->create();
        $enrollment = Enrollment::factory()->completed()->create([
            'user_id'  => $student->id,
            'course_id' => $course->id,
        ]);

        return [$course, $enrollment];
    }

    public function test_certificate_issued_on_course_completion(): void
    {
        Bus::fake([GenerateCertificatePdf::class]);

        [$student] = $this->studentWithToken();
        [$course, $enrollment] = $this->makeCompletedEnrollment($student);

        $service = app(CertificateService::class);
        $service->issue($enrollment);

        $this->assertDatabaseHas('certificates', [
            'enrollment_id' => $enrollment->id,
            'user_id'       => $student->id,
            'course_id'     => $course->id,
        ]);
    }

    public function test_certificate_has_unique_uid(): void
    {
        Bus::fake([GenerateCertificatePdf::class]);

        [$student]  = $this->studentWithToken();
        $student2   = User::factory()->student()->create();
        $course     = Course::factory()->published()->create();

        $enrollment1 = Enrollment::factory()->completed()->create(['user_id' => $student->id, 'course_id' => $course->id]);
        $course2     = Course::factory()->published()->create();
        $enrollment2 = Enrollment::factory()->completed()->create(['user_id' => $student2->id, 'course_id' => $course2->id]);

        $service = app(CertificateService::class);
        $cert1   = $service->issue($enrollment1);
        $cert2   = $service->issue($enrollment2);

        $this->assertNotEquals($cert1->certificate_uid, $cert2->certificate_uid);
    }

    public function test_anyone_can_verify_certificate_by_uid(): void
    {
        Bus::fake([GenerateCertificatePdf::class]);

        [$student] = $this->studentWithToken();
        [$course, $enrollment] = $this->makeCompletedEnrollment($student);

        $service     = app(CertificateService::class);
        $certificate = $service->issue($enrollment);

        $this->getJson("/api/v1/public/certificates/{$certificate->certificate_uid}")
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.uid', $certificate->certificate_uid)
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonStructure(['data' => ['uid', 'student_name', 'course_name', 'instructor_name', 'issued_at', 'is_valid']]);
    }

    public function test_student_can_download_their_certificate(): void
    {
        Storage::fake('public');

        [$student, $token] = $this->studentWithToken();
        [$course, $enrollment] = $this->makeCompletedEnrollment($student);

        $uid = strtoupper(Str::uuid()->toString());
        Storage::disk('public')->put("certificates/{$uid}.pdf", '%PDF-1.4 fake content');

        Certificate::create([
            'user_id'         => $student->id,
            'course_id'       => $course->id,
            'enrollment_id'   => $enrollment->id,
            'certificate_uid' => $uid,
            'issued_at'       => now(),
            'pdf_path'        => "certificates/{$uid}.pdf",
        ]);

        $this->withToken($token)
            ->get("/api/v1/student/certificates/{$uid}/download")
            ->assertStatus(200)
            ->assertDownload("certificate-{$uid}.pdf");
    }

    public function test_student_cannot_download_others_certificate(): void
    {
        Storage::fake('public');

        [$student, $token] = $this->studentWithToken();
        $otherStudent = User::factory()->student()->create();
        $course       = Course::factory()->published()->create();
        $enrollment   = Enrollment::factory()->completed()->create([
            'user_id'  => $otherStudent->id,
            'course_id' => $course->id,
        ]);

        $uid = strtoupper(Str::uuid()->toString());
        Storage::disk('public')->put("certificates/{$uid}.pdf", '%PDF-1.4 fake content');

        Certificate::create([
            'user_id'         => $otherStudent->id,
            'course_id'       => $course->id,
            'enrollment_id'   => $enrollment->id,
            'certificate_uid' => $uid,
            'issued_at'       => now(),
            'pdf_path'        => "certificates/{$uid}.pdf",
        ]);

        $this->withToken($token)
            ->get("/api/v1/student/certificates/{$uid}/download")
            ->assertStatus(404);
    }

    public function test_pdf_generation_job_dispatched_on_certificate_issue(): void
    {
        Bus::fake([GenerateCertificatePdf::class]);

        [$student] = $this->studentWithToken();
        [$course, $enrollment] = $this->makeCompletedEnrollment($student);

        $service = app(CertificateService::class);
        $service->issue($enrollment);

        Bus::assertDispatched(GenerateCertificatePdf::class);
    }
}
