<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;

class ClearCourseCache
{
    public function handle(object $event): void
    {
        Cache::tags(['courses'])->flush();
    }
}
