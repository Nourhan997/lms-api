<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Services\Certificate\CertificateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class IssueCertificate implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly CertificateService $certificateService
    ) {}

    public function handle(CourseCompleted $event): void
    {
        $this->certificateService->issue($event->enrollment);
    }
}
