<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CourseCompleted;
use App\Events\CourseCreated;
use App\Events\CoursePublished;
use App\Events\PaymentCompleted;
use App\Events\StudentEnrolled;
use App\Listeners\ClearCourseCache;
use App\Listeners\IssueCertificate;
use App\Listeners\SendEnrollmentConfirmationEmail;
use App\Listeners\SendPaymentReceiptEmail;
use App\Listeners\ShowNextCoursePrompt;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->bootEvents();
        $this->bootRateLimiters();
    }

    private function bootEvents(): void
    {
        Event::listen(CourseCreated::class, ClearCourseCache::class);
        Event::listen(CoursePublished::class, ClearCourseCache::class);
        Event::listen(StudentEnrolled::class, SendEnrollmentConfirmationEmail::class);
        Event::listen(CourseCompleted::class, IssueCertificate::class);
        Event::listen(CourseCompleted::class, ShowNextCoursePrompt::class);
        Event::listen(PaymentCompleted::class, SendPaymentReceiptEmail::class);
    }

    private function bootRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(10)->by($request->input('email')),
            ];
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
