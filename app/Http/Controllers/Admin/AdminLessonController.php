<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Http\Requests\Admin\UpdateLessonRequest;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\Section;
use App\Services\Course\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminLessonController extends Controller
{
    public function __construct(
        private readonly LessonService $lessonService
    ) {}

    public function index(Section $section): JsonResponse
    {
        $lessons = $this->lessonService->getForSection($section);

        return response()->json([
            'success' => true,
            'data'    => LessonResource::collection($lessons),
            'message' => 'Lessons retrieved successfully.',
            'meta'    => [],
        ]);
    }

    public function store(StoreLessonRequest $request, Section $section): JsonResponse
    {
        $lesson = $this->lessonService->create($section, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new LessonResource($lesson),
            'message' => 'Lesson created successfully.',
            'meta'    => [],
        ], 201);
    }

    public function update(UpdateLessonRequest $request, Section $section, Lesson $lesson): JsonResponse
    {
        $lesson = $this->lessonService->update($lesson, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new LessonResource($lesson),
            'message' => 'Lesson updated successfully.',
            'meta'    => [],
        ]);
    }

    public function destroy(Section $section, Lesson $lesson): Response
    {
        $this->lessonService->delete($lesson);

        return response()->noContent();
    }

    public function reorder(Request $request, Section $section): JsonResponse
    {
        $request->validate([
            'lesson_ids'   => ['required', 'array'],
            'lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ]);

        $this->lessonService->reorder($section, $request->input('lesson_ids'));

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Lessons reordered successfully.',
            'meta'    => [],
        ]);
    }
}
