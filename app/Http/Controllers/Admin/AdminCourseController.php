<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Http\Resources\Admin\AdminCourseResource;
use App\Models\Course;
use App\Services\Admin\AuditService;
use App\Services\Course\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminCourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courseService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @param  Request      $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'category', 'level', 'language', 'search']);
        $courses = $this->courseService->getAllForAdmin($filters);

        return response()->json([
            'success' => true,
            'data'    => AdminCourseResource::collection($courses),
            'message' => 'Courses retrieved successfully.',
            'meta'    => [
                'total'        => $courses->total(),
                'per_page'     => $courses->perPage(),
                'current_page' => $courses->currentPage(),
                'last_page'    => $courses->lastPage(),
            ],
        ]);
    }

    /**
     * @param  StoreCourseRequest $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courseService->create($request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminCourseResource($course),
            'message' => 'Course created successfully.',
            'meta'    => [],
        ], 201);
    }

    /**
     * @param  Course       $course
     * @return JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function show(Course $course): JsonResponse
    {
        $course = $this->courseService->getForAdmin($course);

        return response()->json([
            'success' => true,
            'data'    => new AdminCourseResource($course),
            'message' => 'Course retrieved successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * @param  UpdateCourseRequest $request
     * @param  Course              $course
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course = $this->courseService->update($course, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new AdminCourseResource($course),
            'message' => 'Course updated successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * @param  Course   $course
     * @return Response
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function destroy(Course $course): Response
    {
        $this->courseService->delete($course);

        return response()->noContent();
    }

    /**
     * @param  Course       $course
     * @return JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function publish(Course $course): JsonResponse
    {
        $course = $this->courseService->publish($course);
        $this->auditService->log('course.published', $course);

        return response()->json([
            'success' => true,
            'data'    => new AdminCourseResource($course),
            'message' => 'Course published successfully.',
            'meta'    => [],
        ]);
    }

    /**
     * @param  Course       $course
     * @return JsonResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function archive(Course $course): JsonResponse
    {
        $course = $this->courseService->archive($course);
        $this->auditService->log('course.archived', $course);

        return response()->json([
            'success' => true,
            'data'    => new AdminCourseResource($course),
            'message' => 'Course archived successfully.',
            'meta'    => [],
        ]);
    }
}
