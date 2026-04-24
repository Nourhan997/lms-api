<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\CourseDetailResource;
use App\Http\Resources\Student\CourseListResource;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category', 'level', 'language', 'search', 'type']);
        $courses  = $this->courseService->getPublished($filters);

        return response()->json([
            'success' => true,
            'data'    => CourseListResource::collection($courses),
            'message' => 'Courses retrieved successfully.',
            'meta'    => [
                'total'        => $courses->total(),
                'per_page'     => $courses->perPage(),
                'current_page' => $courses->currentPage(),
                'last_page'    => $courses->lastPage(),
            ],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $course = $this->courseService->getBySlug($slug);

        return response()->json([
            'success' => true,
            'data'    => new CourseDetailResource($course),
            'message' => 'Course retrieved successfully.',
            'meta'    => [],
        ]);
    }
}
