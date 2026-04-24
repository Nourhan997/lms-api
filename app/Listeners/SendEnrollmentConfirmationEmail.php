<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\StudentEnrolled;
use App\Notifications\EnrollmentConfirmationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendEnrollmentConfirmationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(StudentEnrolled $event): void
    {
        $event->student->notify(new EnrollmentConfirmationNotification($event->course));
    }
}
