<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Models\Notification;

class ShowNextCoursePrompt
{
    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment->load('course');

        if (!$enrollment->course->next_course_id) {
            return;
        }

        Notification::create([
            'user_id' => $enrollment->user_id,
            'type'    => 'next_course_prompt',
            'title'   => 'Ready for Your Next Challenge?',
            'body'    => "You've completed {$enrollment->course->title}! Continue your journey with the next course.",
            'data'    => ['next_course_id' => $enrollment->course->next_course_id],
        ]);
    }
}
