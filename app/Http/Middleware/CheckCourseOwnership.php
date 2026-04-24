<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCourseOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $course = $this->resolveCourse($request);

        if (! $course || $course->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'You do not have permission to manage this course.',
                'meta'    => [],
            ], 403);
        }

        return $next($request);
    }

    private function resolveCourse(Request $request): ?Course
    {
        if ($request->route('course') instanceof Course) {
            return $request->route('course');
        }

        if ($request->route('section') instanceof Section) {
            return $request->route('section')->course;
        }

        if ($request->route('lesson') instanceof Lesson) {
            return $request->route('lesson')->section->course;
        }

        return null;
    }
}
