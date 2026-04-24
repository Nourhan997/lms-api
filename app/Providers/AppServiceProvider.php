<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CourseCompleted;
use App\Events\CourseCreated;
use App\Events\CoursePublished;
use App\Events\StudentEnrolled;
use App\Listeners\ClearCourseCache;
use App\Listeners\IssueCertificate;
use App\Listeners\SendEnrollmentConfirmationEmail;
use App\Listeners\ShowNextCoursePrompt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(CourseCreated::class, ClearCourseCache::class);
        Event::listen(CoursePublished::class, ClearCourseCache::class);
        Event::listen(StudentEnrolled::class, SendEnrollmentConfirmationEmail::class);
        Event::listen(CourseCompleted::class, IssueCertificate::class);
        Event::listen(CourseCompleted::class, ShowNextCoursePrompt::class);
    }
}
