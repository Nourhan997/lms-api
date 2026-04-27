<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateCertificatePdf implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;
    public int $uniqueFor = 3600;

    public function __construct(
        private readonly Certificate $certificate
    ) {}

    public function uniqueId(): string
    {
        return "certificate-{$this->certificate->id}";
    }

    public function handle(): void
    {
        $this->certificate->loadMissing(['user', 'course.instructor']);

        $pdf = Pdf::loadView('certificates.template', [
            'studentName'     => $this->certificate->user->name,
            'courseName'      => $this->certificate->course->title,
            'instructorName'  => $this->certificate->course->instructor?->name ?? 'LMS Platform',
            'completionDate'  => $this->certificate->issued_at->format('F j, Y'),
            'certificateUid'  => $this->certificate->certificate_uid,
            'platformName'    => config('app.name', 'LMS Platform'),
        ]);

        $path    = "certificates/{$this->certificate->certificate_uid}.pdf";
        $content = $pdf->output();

        Storage::disk('public')->put($path, $content);

        $this->certificate->update(['pdf_path' => $path]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateCertificatePdf failed', [
            'certificate_id' => $this->certificate->id,
            'error'          => $exception->getMessage(),
        ]);
    }
}
