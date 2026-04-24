<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CourseCreated;
use App\Events\CoursePublished;
use App\Listeners\ClearCourseCache;
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
    }
}
