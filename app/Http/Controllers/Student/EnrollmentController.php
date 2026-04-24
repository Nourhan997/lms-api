<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\EnrollmentResource;
use App\Http\Resources\Student\LessonProgressResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollmentService
    ) {}

    public function enroll(Request $request, Course $course): JsonResponse
    {
        $enrollment = $this->enrollmentService->enroll($request->user(), $course);

        return response()->json([
            'success' => true,
            'data'    => new EnrollmentResource($enrollment),
            'message' => 'Enrolled successfully.',
            'meta'    => [],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $enrollments = $this->enrollmentService->getMyEnrollments($request->user());

        return response()->json([
            'success' => true,
            'data'    => EnrollmentResource::collection($enrollments),
            'message' => 'Enrollments retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function show(Request $request, Enrollment $enrollment): JsonResponse
    {
        if ($enrollment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Enrollment not found.',
                'meta'    => [],
            ], 403);
        }

        $enrollment->load(['course.category', 'course.instructor:id,name']);

        return response()->json([
            'success' => true,
            'data'    => new EnrollmentResource($enrollment),
            'message' => 'Enrollment retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function progress(Request $request, Enrollment $enrollment): JsonResponse
    {
        if ($enrollment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Enrollment not found.',
                'meta'    => [],
            ], 403);
        }

        $progress = $this->enrollmentService->getCourseProgress($request->user(), $enrollment);

        return response()->json([
            'success' => true,
            'data'    => $progress,
            'message' => 'Progress retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function completeLesson(Request $request, Lesson $lesson): JsonResponse
    {
        $lesson->load('section.course');
        $enrollment = $this->enrollmentService->getEnrollment($request->user(), $lesson->section->course);
        $progress   = $this->enrollmentService->completeLesson($request->user(), $lesson, $enrollment);

        return response()->json([
            'success' => true,
            'data'    => new LessonProgressResource($progress),
            'message' => 'Lesson marked as complete.',
            'meta'    => [],
        ]);
    }
}
